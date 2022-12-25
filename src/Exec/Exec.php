<?php

declare(strict_types=1);
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

use jzfpost\ssh2\SshException;
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

        $context = $this->context + ['{cmd}' => $cmd];
        $this->logger->notice("Trying execute \"{cmd}\"...", $context);

        if ($this->session->isConnected()) {
            $this->startTimer();

            $exec = ssh2_exec(
                $this->session->getSession(),
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
                    throw new SshException("Failed to execute \"$cmd\"", $this->logger, $this->context);
                }

                $this->logger->info(
                    "Command execution time is {timer} microseconds",
                    $this->context + ['{timer}' => (string) $timer]
                );

                $this->logger->debug($content, $this->context);
                $this->logger->info("Data transmission is over", $this->context);

                return trim($content);
            }
        }

        throw new SshException("Unable to exec command", $this->logger, $this->context);
    }

    public function __destruct()
    {
        $this->close();
    }
}