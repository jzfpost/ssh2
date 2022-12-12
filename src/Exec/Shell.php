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
use function preg_match;
use function preg_replace;
use function ssh2_shell;
use function str_contains;
use function stream_get_meta_data;
use function stream_set_timeout;
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
     * @psalm-suppress PossiblyNullArgument
     * @throws SshException
     */
    public function open(string $prompt): ShellInterface
    {
        $this->checkConnectionEstablished();

        $this->logger->notice("Trying opening shell...", $this->ssh->getLogContext());

        if (is_resource($this->shell)) {
            throw new SshException("Already opened shell");
        }

        $session = $this->ssh->getSession();
        if (is_resource($session)) {
            $this->shell = ssh2_shell(
                $session,
                $this->configuration->getTermType(),
                $this->configuration->getEnv(),
                $this->configuration->getWidth(),
                $this->configuration->getHeight(),
                $this->configuration->getWidthHeightType()
            );

            if ($this->isOpened()) {

                $this->logger->notice("Shell opened success", $this->ssh->getLogContext());

                $this->fetchStream($this->shell);

                $this->readTo($prompt);

                return $this;
            }
        }

        $message = "Unable to establish shell";
        $this->logger->critical($message, $this->ssh->getLogContext());
        throw new SshException($message);
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

        parent::close();

        $this->shell = false;
    }

    /**
     * @throws SshException
     */
    public function exec(string $cmd): string
    {
        $context = $this->ssh->getLogContext() + ['{cmd}' => $cmd];
        $this->logger->notice("Trying execute \"{cmd}\"...", $context);

        if (is_resource($this->shell)) {

            $this->write($cmd);

            $content = $this->getStreamContent($this->shell);
            $timer = $this->stopTimer();

            if (false === $content) {
                $message = "Failed to execute \"$cmd\"";
                $this->logger->critical($message, $context);
                throw new SshException($message);
            }

            $this->logger->info(
                "Command execution time is {timer} microseconds",
                $this->ssh->getLogContext() + ['{timer}' => (string) $timer]
            );

            $this->logger->debug($content, $this->ssh->getLogContext());
            $this->logger->info("Data transmission is over", $this->ssh->getLogContext());

            return $this->trimFirstLine(trim($content));
        }

        $message = "Open shell first!";
        $this->logger->critical($message, $this->ssh->getLogContext());
        throw new SshException($message);
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

        $message = "Open shell first!";
        $this->logger->critical($message, $this->ssh->getLogContext());
        throw new SshException($message);
    }

    /**
     * Write command to a shell socket.
     * @throws SshException
     */
    private function write(string $cmd): void
    {
        if (is_resource($this->shell)) {
            $this->logger->notice("Write command to $this->ssh => \"{cmd}\"", $this->ssh->getLogContext() + ['{cmd}' => $cmd]);

            $this->startTimer();


            fwrite($this->shell, trim($cmd) . PHP_EOL);
        } else {
            $message = "Failed to execute \"$cmd\"";
            $this->logger->critical($message, $this->ssh->getLogContext());
            throw new SshException($message);
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
                "Set prompt to \"{prompt}\" on shell",
                $this->ssh->getLogContext() + ['{prompt}' => $prompt]
            );

            usleep($this->configuration->getWait());

            stream_set_timeout($this->shell, $this->configuration->getTimeout());

            $buffer = '';

            do {
                $c = @fgetc($this->shell);

                if (false === $c) {
                    $this->logger->info(
                        "Timeout released before the prompt was read shell stream",
                        $this->ssh->getLogContext()
                    );

                    break;
                }

                $buffer .= $c;

                if (preg_match("/$prompt\s?$/im", $buffer)) {
                    break;
                }

            } while (stream_get_meta_data($this->shell)["eof"] === false);

            $timer = $this->stopTimer();

            @fflush($this->shell);

            $this->logger->info(
                "Command execution time is {timer} microseconds",
                $this->ssh->getLogContext() + ['{timer}' => (string) $timer]
            );

            $this->logger->debug($buffer, $this->ssh->getLogContext());
            $this->logger->info(
                "Data transmission is over on shell",
                $this->ssh->getLogContext()
            );

            return $buffer;
        }

        $message = "Failed reed resource";
        $this->logger->critical($message, $this->ssh->getLogContext());
        throw new SshException($message);
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

    public function __destruct()
    {
        $this->close();
    }
}