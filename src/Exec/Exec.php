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
use function ssh2_exec;
use function ssh2_fetch_stream;
use function stream_set_blocking;
use function microtime;

final class Exec extends AbstractExec
{

    public function exec(string $cmd): string|false
    {
        $context = $this->ssh->getLogContext() + ['{cmd}' => $cmd];
        $this->logger->notice("Trying execute '{cmd}' at {host}:{port} connection", $context);

        $session = $this->ssh->getSession();
        if (is_resource($session)) {
            $this->executeTimestamp = microtime(true);

            $exec = @ssh2_exec(
                $session,
                $cmd,
                $this->configuration->getPty(),
                $this->configuration->getEnv(),
                $this->configuration->getWidth(),
                $this->configuration->getHeight(),
                $this->configuration->getWidthHeightType()->getValue()
            );

            $this->stderr = ssh2_fetch_stream($exec, SSH2_STREAM_STDERR);
            stream_set_blocking($this->stderr, true);
            stream_set_blocking($exec, true);

            usleep($this->configuration->getWait());

            stream_set_timeout($exec, $this->configuration->getTimeout());

            $content = stream_get_contents($exec);
            if (false === $content) {
                $this->logger->critical("Failed to execute '{cmd}' at {host}:{port}", $context);
                throw new SshException("Failed to execute '{cmd}' at $this->ssh");
            }

            $timestamp = microtime(true) - $this->executeTimestamp;

            fflush($exec);

            $this->logger->info(
                "Command execution time is {timestamp} microseconds",
                $this->ssh->getLogContext() + ['{timestamp}' => (string) $timestamp]
            );

            $this->logger->debug($content, $this->ssh->getLogContext());
            $this->logger->info("Data transmission is over at {host}:{port} connection", $this->ssh->getLogContext());

            return $content;
        }

        $this->logger->critical("Unable to exec command at {host}:{port} connection", $this->ssh->getLogContext());
        throw new SshException("Unable to exec command at $this->ssh connection");
    }

    public function close(): void
    {
        $stdErr = $this->getStderr();
        if (is_resource($stdErr)) {
            fflush($stdErr);
            !@fclose($stdErr);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

}