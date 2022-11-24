<?php declare(strict_types=1);
/**
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://github/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 */

namespace jzfpost\ssh2\Exec;

use jzfpost\ssh2\Exceptions\SshException;

use function is_resource;
use function ssh2_shell;
use function ssh2_fetch_stream;
use function stream_set_blocking;
use function microtime;
use function utf8_encode;
use function mb_convert_encoding;
use function trim;
use function fwrite;
use function fclose;
use function preg_match;
use function preg_replace;
use function substr;
use function strpos;
use function usleep;
use function fgetc;

final class Shell extends AbstractExec implements ShellInterface, ExecInterface
{
    /**
     * Commands turn off pagination on terminal
     */
    public const TERMINAL_PAGINATION_OFF_CISCO = 'terminal length 0';
    public const TERMINAL_PAGINATION_OFF_HUAWEI = 'screen-length 0 temporary';
    public const TERMINAL_PAGINATION_OFF_DLINK = 'disable clipaging';

    /**
     * RegExp prompts
     */
    public const PROMPT_LINUX = '[^@]@[^:]+:~\$';
    public const PROMPT_LINUX_SU = 'root@[^:]+:[^#]+#';
    public const PROMPT_CISCO = '^[\w._-]+[#>]';
    public const PROMPT_HUAWEI = '[[<]~?[\w._-]+[]>]';

    /**
     * @var resource|closed-resource|false shell
     */
    private mixed $shell = false;
    private string $buffer = '';

    /**
     * @inheritDoc
     * @throws SshException
     */
    public function open(string $prompt): ShellInterface
    {
        $this->logger->info('Trying opening shell at {host}:{port} connection', $this->ssh->getLogContext());

        if (is_resource($this->shell)) {
            throw new SshException("Already opened shell at $this->ssh connection");
        }

        $session = $this->ssh->getSession();
        if (is_resource($session)) {
            $this->shell = @ssh2_shell(
                $session,
                $this->configuration->getTermType()->getValue(),
                $this->configuration->getEnv(),
                $this->configuration->getWidth(),
                $this->configuration->getHeight(),
                $this->configuration->getWidthHeightType()->getValue()
            );

            if ($this->isOpened()) {

                $this->logger->info('Shell opened success at {host}:{port} connection', $this->ssh->getLogContext());

                $this->stderr = ssh2_fetch_stream($this->shell, SSH2_STREAM_STDERR);
                stream_set_blocking($this->stderr, true);
                stream_set_blocking($this->shell, true);

                $this->readTo($prompt);
                $this->clearBuffer();

                return $this;
            }
        }

        $this->logger->critical("Unable to establish shell at {host}:{port} connection", $this->ssh->getLogContext());
        throw new SshException("Unable to establish shell at $this->ssh connection");
    }

    public function isOpened(): bool
    {
        return is_resource($this->shell);
    }

    public function close(): void
    {
        $this->clearBuffer();
        if (is_resource($this->shell)) {
            fflush($this->shell);
            if (!@fclose($this->shell)) {
                $this->logger->critical('Shell stream closes is fail.', $this->ssh->getLogContext());
            }
        }

        if (is_resource($this->stderr)) {
            fflush($this->stderr);
            if (!@fclose($this->stderr)) {
                $this->logger->critical('Errors stream closes is fail.', $this->ssh->getLogContext());
            }
        }

        $this->shell = false;
        $this->stderr = false;
    }

    /**
     * @throws SshException
     */
    public function exec(string $cmd): string|false
    {
        if (is_resource($this->shell)) {
            $this->write($cmd);

            usleep($this->configuration->getWait());

            stream_set_timeout($this->shell, $this->configuration->getTimeout());
            $this->buffer = stream_get_contents($this->shell);

            fflush($this->shell);

            $buffer = $this->trimFirstLine(trim($this->buffer));

            $encoding = $this->configuration->getEncoding();
            if (is_string($encoding)) {
                return mb_convert_encoding($buffer, $encoding);
            }
            return utf8_encode($buffer);
        }

        $this->logger->critical("Open shell first on {host}:{port} connection", $this->ssh->getLogContext());
        throw new SshException("Open shell first on $this->ssh connection");
    }

    /**
     * @inheritDoc
     * @throws SshException
     */
    public function send(string $cmd, string $prompt): string
    {
        if (is_resource($this->shell)) {
            $this->write($cmd);
            $this->readTo($prompt);

            $buffer = $this->trimFirstLine(trim($this->buffer));
            $buffer = $this->trimPrompt($buffer, $prompt);

            $encoding = $this->configuration->getEncoding();
            if (is_string($encoding)) {
                return mb_convert_encoding($buffer, $encoding);
            }

            return utf8_encode($buffer);
        }

        $this->logger->critical("Open shell first on {host}:{port} connection", $this->ssh->getLogContext());
        throw new SshException("Open shell first on $this->ssh connection");
    }

    /**
     * Write command to a socket.
     *
     * @param string $cmd Stuff to write to socket
     * @return void
     * @throws SshException
     */
    private function write(string $cmd): void
    {
        $this->clearBuffer();

        $this->logger->info('Write command to host {host}:{port} => "{cmd}"', $this->ssh->getLogContext() + ['{cmd}' => $cmd]);

        $this->executeTimestamp = microtime(true);

        if (is_resource($this->shell) && (!@fwrite($this->shell, trim($cmd) . PHP_EOL)) < 0) {
            $this->logger->critical("Error writing to shell at {host}:{port} connection", $this->ssh->getLogContext());
            throw new SshException("Error writing to shell at $this->ssh connection");
        }
    }

    /**
     * Reads characters from the shell and adds them to command buffer.
     * Handles telnet control characters. Stops when prompt is encountered.
     *
     * @param string $prompt
     * @return void
     * @throws SshException
     */
    private function readTo(string $prompt): void
    {
        if(is_resource($this->shell)) {
            $this->logger->info(
                'Set prompt to "{prompt}" on shell at {host}:{port} connection',
                $this->ssh->getLogContext() + ['{prompt}' => $prompt]
            );

            $this->clearBuffer();

            usleep($this->configuration->getWait());

            stream_set_timeout($this->shell, $this->configuration->getTimeout());

            do {
                $c = @fgetc($this->shell);

                if (false === $c) {
                    $this->logger->info(
                        "Timeout released before the prompt was read on shell at {host}:{port} connection",
                        $this->ssh->getLogContext()
                    );

                    break;
                }

                $this->buffer .= $c;

                if (preg_match("/$prompt\s?$/im", $this->buffer)) {
                    $timestamp = microtime(true) - $this->executeTimestamp;
                    $this->logger->info(
                        "Command execution time is {timestamp} microseconds",
                        $this->ssh->getLogContext() + ['{timestamp}' => (string)$timestamp]
                    );

                    break;
                }
            } while (stream_get_meta_data($this->shell)["eof"] === false);

            fflush($this->shell);

            $this->logger->log('none', PHP_EOL);
            $this->logger->debug($this->buffer, $this->ssh->getLogContext());
            $this->logger->info("Data transmission is over on shell at {host}:{port} connection", $this->ssh->getLogContext());
        }
    }

    /**
     * Trim the first line of multiline text
     * @param string $text
     * @return string
     */
    private function trimFirstLine(string $text): string
    {
        if (!empty($text) && str_contains($text, PHP_EOL)) {
            return substr($text, (int) strpos($text, PHP_EOL, 1) + 1);
        }
        return $text;
    }

    /**
     * Trim the prompt string of multiline text
     * @param string $text
     * @param string $prompt
     * @return string
     */
    private function trimPrompt(string $text, string $prompt): string
    {
        return preg_replace("/$prompt\s*$/i", '', $text);
    }

    /**
     * Clears internal command buffer.
     *
     * @return void
     */
    private function clearBuffer(): void
    {
        $this->buffer = '';
    }

    /**
     * Destructor.
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }
}