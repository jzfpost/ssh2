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
use jzfpost\ssh2\Conf\FPAlgorithmEnum;
use jzfpost\ssh2\SshException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;
use function function_exists;
use function is_array;
use function is_resource;
use function register_shutdown_function;
use function sprintf;
use function ssh2_connect;
use function ssh2_disconnect;
use function ssh2_fingerprint;
use function ssh2_methods_negotiated;

final class Session implements SessionInterface, Configurable, Stringable
{
    private string $host = 'localhost';
    /**
     * @var positive-int
     */
    private int $port = 22;
    /**
     * @var resource|closed-resource|false
     */
    private mixed $session = false;
    private ?string $fingerPrint = null;
    private ?array $methodsNegotiated = null;

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

        $this->session = @ssh2_connect(
            $host,
            $port,
            $this->configuration->getMethods(),
            $this->configuration->getCallbacks()
        );

        if (!$this->isConnected()) {
            throw new SshException("Unable connection to $this", $this->logger, $this->context);
        }

        $this->logger->notice("Connection established success", $this->context);

        $this->getFingerPrint($this->configuration->getFingerPrintAlgorithmEnum());
        $this->getMethodsNegotiated();

        return $this;
    }

    public function isConnected(): bool
    {
        return is_resource($this->session);
    }

    /**
     * Retrieve fingerprint of remote server
     * @return string the hostkey hash as a string
     * @throws SshException
     */
    public function getFingerPrint(FPAlgorithmEnum $algorithm = FPAlgorithmEnum::md5): string
    {
        if ($this->fingerPrint === null) {
            $this->fingerPrint = is_resource($this->session)
                ? ssh2_fingerprint($this->session, $algorithm->getValue())
                : throw new SshException("Not established ssh2 session", $this->logger, $this->context);

            $this->logger->notice("Retrieve fingerPrint algorithm: $algorithm->name", $this->context);
            $this->logger->debug($this->fingerPrint, $this->context);
        }

        return $this->fingerPrint;
    }

    /**
     * Return list of negotiated methods
     */
    public function getMethodsNegotiated(): array
    {
        if ($this->methodsNegotiated === null) {
            $this->methodsNegotiated = is_resource($this->session)
                ? ssh2_methods_negotiated($this->session)
                : throw new SshException("Not established ssh2 session", $this->logger, $this->context);

            if (is_array($this->methodsNegotiated)) {
                $this->logger->notice("Methods negotiated:", $this->context);
                $this->logger->debug(print_r($this->methodsNegotiated, true), $this->context);
            } else {
                $this->logger->warning("No methods negotiated:", $this->context);
            }
        }

        return $this->methodsNegotiated;
    }

    /**
     * @inheritDoc
     * @throws SshException
     */
    public function getSession(): mixed
    {
        if (is_resource($this->session)) {
            return $this->session;
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
        if (is_resource($this->session) && @ssh2_disconnect($this->session)) {
            $this->session = false;
            $this->logger->notice('Disconnection complete', $this->context);
        }
    }
}