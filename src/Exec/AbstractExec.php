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
use jzfpost\ssh2\Exceptions\SshException;
use jzfpost\ssh2\SshInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function is_resource;
use function microtime;

abstract class AbstractExec implements ExecInterface
{

    protected float $executeTimestamp;
    /**
     * @var resource|closed-resource|false errors
     */
    protected mixed $stderr = false;

    public function __construct(
        protected SshInterface $ssh,
        protected              readonly Configuration $configuration = new Configuration(),
        public LoggerInterface $logger = new NullLogger)
    {
        $conf = $this->configuration->getAsArray();
        $this->executeTimestamp = microtime(true);

        $this->logger->info(
            "{property} set to {value}",
            $ssh->getLogContext() + ['{property}' => 'TERMTYPE', '{value}' => $configuration->getTermType()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $ssh->getLogContext() + ['{property}' => 'WIDTH', '{value}' => (string) $configuration->getWidth()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $ssh->getLogContext() + ['{property}' => 'HEIGHT', '{value}' => (string) $configuration->getHeight()]
        );

        $this->logger->info(
            "{property} set to {value}",
            $ssh->getLogContext() + ['{property}' => 'WIDTHHEIGHTTYPE', '{value}' => $configuration->getWidthHeightType()]
        );

        if ($conf['env'] === null) {
            $this->logger->info(
                "{property} set to {value}",
                $ssh->getLogContext() + ['{property}' => 'ENV', '{value}' => 'NULL']
            );
        } else {
            /**
             * @psalm-var string $key
             * @psalm-var string $value
             */
            foreach ($conf['env'] as $key => $value) {
                $this->logger->info(
                    "{property} set to {value}",
                    $ssh->getLogContext() + ['{property}' => 'ENV', '{value}' => $key . ' => ' . $value]
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
            $message = "Failed connection";
            $this->logger->critical($message, $this->ssh->getLogContext());
            throw new SshException($message);
        }
        if (false === $this->ssh->isAuthorised()) {
            $message = "Failed authorisation";
            $this->logger->critical($message, $this->ssh->getLogContext());
            throw new SshException($message);
        }
    }

}