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

use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\SshInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function is_resource;
use function microtime;

abstract class AbstractExec implements ExecInterface
{

    protected readonly Configuration $configuration;
    protected float $executeTimestamp;

    /**
     * @var resource|closed-resource|false errors
     */
    protected mixed $stderr = false;

    public function __construct(protected SshInterface $ssh, public LoggerInterface $logger = new NullLogger)
    {
        $this->configuration = $this->ssh->getConfiguration();

        $this->executeTimestamp = microtime(true);

        $this->logger->info(
            "{property} set to {value}",
            $this->ssh->getLogContext() + ['{property}' => 'TERMTYPE', '{value}' => $this->configuration->getTermType()->getValue()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $this->ssh->getLogContext() + ['{property}' => 'WIDTH', '{value}' => (string) $this->configuration->getWidth()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $this->ssh->getLogContext() + ['{property}' => 'HEIGHT', '{value}' => (string) $this->configuration->getHeight()]
        );

        $this->logger->info(
            "{property} set to {value}",
            $this->ssh->getLogContext() + ['{property}' => 'WIDTHHEIGHTTYPE', '{value}' => $this->configuration->getWidthHeightType()->name]
        );

        $env = $this->configuration->getEnv();
        if ($env === null) {
            $this->logger->info(
                "{property} set to {value}",
                $this->ssh->getLogContext() + ['{property}' => 'ENV', '{value}' => 'NULL']
            );
        } else {
            foreach ($env as $key => $value) {
                $this->logger->info(
                    "{property} set to {value}",
                    $this->ssh->getLogContext() + ['{property}' => 'ENV', '{value}' => $key . ' => ' . $value]
                );
            }
        }
    }

    abstract public function exec(string $cmd): string;

    abstract public function close(): void;

    /**
     * @psalm-return resource|false
     */
    public function getStderr(): mixed
    {
        return is_resource($this->stderr) ? $this->stderr : false;
    }

    protected function checkConnectionEstablished(): void
    {
        if (!$this->ssh->isConnected()) {
            $this->ssh->loggedException("Failed connecting to host $this->ssh");
        }
        if (false === $this->ssh->isAuthorised()) {
            $this->ssh->loggedException("Failed authorisation on host $this->ssh");
        }
    }

}