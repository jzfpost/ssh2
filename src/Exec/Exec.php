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
use function trim;

final class Exec extends AbstractExec
{

    /**
     * @psalm-suppress PossiblyNullArgument
     * @throws SshException
     */
    public function exec(string $cmd): string
    {
        $this->checkConnectionEstablished();

        $context = $this->ssh->getLogContext() + ['{cmd}' => $cmd];
        $this->logger->notice("Trying execute \"{cmd}\"...", $context);

        $session = $this->ssh->getSession();
        if (is_resource($session)) {
            $this->startTimer();

            $exec = ssh2_exec(
                $session,
                trim($cmd),
                $this->configuration->getTermType(),
                $this->configuration->getEnv(),
                $this->configuration->getWidth(),
                $this->configuration->getHeight(),
                $this->configuration->getWidthHeightType()
            );

            if (is_resource($exec)) {
                $this->fetchStream($exec);

                $content = $this->getStreamContent($exec);
                $timer = $this->stopTimer();

                if (false === $content) {
                    $message = "Failed to execute \"$cmd\"";
                    $this->logger->critical($message, $this->ssh->getLogContext());
                    throw new SshException($message);
                }

                $this->logger->info(
                    "Command execution time is {timer} microseconds",
                    $this->ssh->getLogContext() + ['{timer}' => (string) $timer]
                );

                $this->logger->debug($content, $this->ssh->getLogContext());
                $this->logger->info("Data transmission is over", $this->ssh->getLogContext());

                return trim($content);
            }
        }

        $message = "Unable to exec command";
        $this->logger->critical($message, $this->ssh->getLogContext());
        throw new SshException($message);
    }

    public function __destruct()
    {
        $this->close();
    }

}