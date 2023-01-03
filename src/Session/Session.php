<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Session;

use jzfpost\ssh2\Conf\Configurable;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\SshException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;
use function function_exists;
use function is_resource;
use function register_shutdown_function;
use function sprintf;
use function ssh2_connect;
use function ssh2_disconnect;

final class Session implements SessionInterface, Configurable, Stringable
{
    /**
     * @var non-empty-string
     */
    private string $host = 'localhost';
    /**
     * @var positive-int
     */
    private int $port = 22;
    /**
     * @var resource|closed-resource|false
     */
    private mixed $connection = false;

    public function __construct(
        public Configuration   $configuration = new Configuration(),
        public LoggerInterface $logger = new NullLogger(),
        private readonly array $context = []
    )
    {
        if (!function_exists('ssh2_connect')) {
            throw new SshException("ssh2_connect function doesn't exist! Please install \"ext-ssh2\" php module.", $this->logger);
        }

        register_shutdown_function([$this, 'disconnect']);
    }

    /**
     * @inheritDoc
     * @psalm-suppress PossiblyNullArgument
     */
    public function connect(string $host = 'localhost', int $port = 22): self
    {
        $this->logger->notice("Trying connection...", $this->context);

        $this->connection = @ssh2_connect(
            $host,
            $port,
            $this->configuration->getMethods(),
            $this->configuration->getCallbacks()
        );

        if (!$this->isConnected()) {
            throw new SshException("Unable connection to $this", $this->logger, $this->context);
        }

        $this->logger->notice("Connection established success", $this->context);

        return $this;
    }

    public function isConnected(): bool
    {
        return is_resource($this->connection);
    }

    /**
     * @inheritDoc
     * @throws SshException
     */
    public function getConnection(): mixed
    {
        if (is_resource($this->connection)) {
            return $this->connection;
        }

        throw new SshException("Unable connection to $this", $this->logger, $this->context);
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('host %s:%d', $this->host, $this->port);
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    public function disconnect(): void
    {
        if (is_resource($this->connection) && @ssh2_disconnect($this->connection)) {
            $this->connection = false;
            $this->logger->notice('Disconnection complete', $this->context);
        }
    }
}