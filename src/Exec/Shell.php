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
use function fclose;
use function fflush;
use function fgetc;
use function fwrite;
use function is_resource;
use function microtime;
use function preg_match;
use function preg_replace;
use function ssh2_fetch_stream;
use function ssh2_shell;
use function stream_get_contents;
use function stream_set_blocking;
use function strpos;
use function substr;
use function trim;
use function usleep;

final class Shell extends AbstractExec implements ShellInterface
{

    /**
     * @var resource|closed-resource|false shell
     */
    private mixed $shell = false;

    /**
     * @throws SshException
     */
    public function open(string $prompt): ShellInterface
    {
        $this->checkConnectionEstablished();

        $this->logger->notice('Trying opening shell at {host}:{port} connection', $this->ssh->getLogContext());

        if (is_resource($this->shell)) {
            throw new SshException("Already opened shell at $this->ssh connection");
        }

        $session = $this->ssh->getSession();
        if (is_resource($session)) {
            $this->shell = ssh2_shell(
                $session,
                $this->configuration->getTermType()->getValue(),
                $this->configuration->getEnv(),
                $this->configuration->getWidth(),
                $this->configuration->getHeight(),
                $this->configuration->getWidthHeightType()->getValue()
            );

            if ($this->isOpened()) {

                $this->logger->notice('Shell opened success at {host}:{port} connection', $this->ssh->getLogContext());

                $this->stderr = ssh2_fetch_stream($this->shell, SSH2_STREAM_STDERR);
                stream_set_blocking($this->stderr, true);
                stream_set_blocking($this->shell, true);

                $this->readTo($prompt);

                return $this;
            }
        }

        $this->ssh->loggedException("Unable to establish shell at $this->ssh connection");
    }

    public function isOpened(): bool
    {
        return is_resource($this->shell);
    }

    public function close(): void
    {
        if (is_resource($this->shell)) {
            @fflush($this->shell);
            if (!@fclose($this->shell)) {
                $this->logger->critical('Shell stream closes is fail.', $this->ssh->getLogContext());
            }
        }

        if (is_resource($this->stderr)) {
            @fflush($this->stderr);
            @fclose($this->stderr);
        }

        $this->shell = false;
        $this->stderr = false;
    }

    /**
     * @throws SshException
     */
    public function exec(string $cmd): string
    {
        $context = $this->ssh->getLogContext() + ['{cmd}' => $cmd];
        $this->logger->notice("Trying execute '{cmd}' at {host}:{port} connection", $context);

        if (is_resource($this->shell)) {

            $this->write($cmd);

            usleep($this->configuration->getWait());

            stream_set_timeout($this->shell, $this->configuration->getTimeout());

            $content = stream_get_contents($this->shell);
            if (false === $content) {
                $this->ssh->loggedException("Failed to execute '{cmd}' at $this->ssh", $context);
            }

            @fflush($this->shell);

            return $this->trimFirstLine(trim($content));
        }

        $this->ssh->loggedException("Open shell first on $this->ssh connection");
    }

    /**
     * @inheritDoc
     * @throws SshException
     */
    public function send(string $cmd, string $prompt): string
    {
        if (is_resource($this->shell)) {
            $this->write($cmd);
            $content = $this->readTo($prompt);

            $content = $this->trimFirstLine(trim($content));
            return $this->trimPrompt($content, $prompt);
        }

        $this->ssh->loggedException("Open shell first on $this->ssh connection");
    }

    /**
     * Write command to a shell socket.
     * @throws SshException
     */
    private function write(string $cmd): void
    {
        if (is_resource($this->shell)) {
            $this->logger->notice('Write command to host {host}:{port} => "{cmd}"', $this->ssh->getLogContext() + ['{cmd}' => $cmd]);

            $this->executeTimestamp = microtime(true);

            fwrite($this->shell, trim($cmd) . PHP_EOL);
        } else {
            throw new SshException("Failed to execute \"$cmd\" at $this->ssh");
        }
    }

    /**
     * Reads characters from the shell and adds them to command buffer.
     * Handles telnet control characters. Stops when prompt is encountered.
     * @throws SshException
     */
    private function readTo(string $prompt): string
    {
        if (is_resource($this->shell)) {
            $this->logger->info(
                'Set prompt to "{prompt}" on shell at {host}:{port} connection',
                $this->ssh->getLogContext() + ['{prompt}' => $prompt]
            );

            usleep($this->configuration->getWait());

            stream_set_timeout($this->shell, $this->configuration->getTimeout());

            $buffer = '';

            do {
                $c = @fgetc($this->shell);

                if (false === $c) {
                    $this->logger->info(
                        "Timeout released before the prompt was read on shell at {host}:{port} connection",
                        $this->ssh->getLogContext()
                    );

                    break;
                }

                $buffer .= $c;

                if (preg_match("/$prompt\s?$/im", $buffer)) {
                    break;
                }

            } while (stream_get_meta_data($this->shell)["eof"] === false);

            @fflush($this->shell);

            $timestamp = microtime(true) - $this->executeTimestamp;
            $this->logger->info(
                "Command execution time is {timestamp} microseconds",
                $this->ssh->getLogContext() + ['{timestamp}' => (string) $timestamp]
            );

            $this->logger->debug($buffer, $this->ssh->getLogContext());
            $this->logger->info(
                "Data transmission is over on shell at {host}:{port} connection",
                $this->ssh->getLogContext()
            );

            return $buffer;
        }
        throw new SshException("Failed reed resource on $this->ssh");
    }

    /**
     * Trim the first line of multiline text
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
     */
    private function trimPrompt(string $text, string $prompt): string
    {
        return preg_replace("/$prompt\s*$/i", '', $text);
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