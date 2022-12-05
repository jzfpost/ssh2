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

namespace jzfpost\ssh2;

use JetBrains\PhpStorm\ArrayShape;
use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Conf\FPAlgorithmEnum;
use jzfpost\ssh2\Conf\TypeEnumInterface;
use jzfpost\ssh2\Exceptions\SshException;
use jzfpost\ssh2\Exec\Exec;
use jzfpost\ssh2\Exec\ExecInterface;
use jzfpost\ssh2\Exec\Shell;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function function_exists;
use function is_resource;
use function register_shutdown_function;
use function ssh2_auth_none;
use function ssh2_connect;
use function ssh2_disconnect;
use function ssh2_fingerprint;
use function ssh2_methods_negotiated;
use function ucfirst;

/**
 *
 * USAGE:
 * ```php
 * $conf = (new Configuration('192.168.1.1'))
 *  ->setTermType('dumb');
 *
 * $ssh2 = new Ssh($conf);
 * $ssh2->connect()
 *  ->authPassword($username, $password);
 *
 * // on router use shell
 * $shell = $ssh2->getShell()
 *  ->open(PromptEnum::cisco->value);
 * $shell->send('terminal length 0', PromptEnum::cisco->value);
 * $result = $shell->send('show version', PromptEnum::cisco->value);
 *
 * //or on linux server use exec
 * $exec = $ssh2->getExec();
 * $result = $exec->exec('ls -a');
 *
 *
 * $ssh2->disconnect();
 * ```
 *
 * @property-read non-empty-string $host
 * @property-read positive-int $port
 * @property-read positive-int $timeout
 * @property-read positive-int $wait
 * @property-read string|false $loggingFileName
 * @property-read string $dateFormat
 * @property-read array $methods
 * @property-read array $callbacks
 * @property-read string|null $termType
 * @property-read array|null $env
 * @property-read int $width
 * @property-read int $height
 * @property-read int $widthHeightType
 * @property-read string|null $pty
 */
final class Ssh implements SshInterface
{
    private bool $isAuthorised = false;
    private ?AuthInterface $auth = null;
    private ?ExecInterface $exec = null;
    /**
     * @var resource|closed-resource|false
     */
    private mixed $session = false;
    private ?string $fingerPrint = null;
    private null|bool|array $authMethods = null;
    private ?array $methodsNegotiated = null;
    /**
     * @var array<string, string>
     */
    private array $context = [];

    public function __construct(
        private                 readonly Configuration $configuration = new Configuration(),
        private LoggerInterface $logger = new NullLogger()
    )
    {
        if (!function_exists('ssh2_connect')) {
            throw new SshException("ssh2_connect function doesn't exist! Please install \"ext-ssh2\" php module.");
        }

        register_shutdown_function([$this, 'disconnect']);
    }

    public function connect(string $host = 'localhost', int $port = 22, LoggerInterface $logger = null): SshInterface
    {
        $new = new self($this->configuration, $logger ?? $this->logger);

        $new->setContext($host, $port);

        $new->logger->notice("Trying connection...", $new->getLogContext());

        $new->session = ssh2_connect($host, $port, $new->methods, $new->callbacks);

        if (!is_resource($new->session)) {
            $new->loggedException("Connection refused");
        }

        $new->logger->notice("Connection established success", $new->getLogContext());

        $new->getFingerPrint();
        $new->getMethodsNegotiated();

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getSession(): mixed
    {
        if (is_resource($this->session)) {
            return $this->session;
        }
        return false;
    }

    public function isConnected(): bool
    {
        return is_resource($this->session);
    }

    public function disconnect(): void
    {
        if ($this->exec instanceof ExecInterface) {
            $this->exec->close();
            $this->exec = null;
        }

        $this->isAuthorised = false;

        if (is_resource($this->session) && @ssh2_disconnect($this->session)) {
            $this->session = false;
            $this->logger->notice('Disconnection complete', $this->getLogContext());
        }
    }

    public function getExec(): Exec
    {
        return $this->exec instanceof Exec ? $this->exec : $this->exec = new Exec($this, $this->configuration, $this->logger);
    }

    public function getShell(): Shell
    {
        return $this->exec instanceof Shell ? $this->exec : $this->exec = new Shell($this, $this->configuration, $this->logger);
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Return list of negotiated methods
     */
    public function getMethodsNegotiated(): array
    {
        if ($this->methodsNegotiated === null) {
            $this->methodsNegotiated = is_resource($this->session)
                ? ssh2_methods_negotiated($this->session)
                : throw new SshException("Not established ssh2 session");

            if (is_array($this->methodsNegotiated)) {
                $this->logger->notice("Methods negotiated:", $this->getLogContext());
                $this->logger->debug(print_r($this->methodsNegotiated, true), $this->getLogContext());
            } else {
                $this->logger->warning("No methods negotiated:", $this->getLogContext());
            }
        }

        return $this->methodsNegotiated;
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
                : throw new SshException("Not established ssh2 session");

            $this->logger->notice("Retrieve fingerPrint:", $this->getLogContext());
            $this->logger->debug($this->fingerPrint, $this->getLogContext());
        }

        return $this->fingerPrint;
    }

    /**
     * Return an array of accepted authentication methods.
     * Return true if the server does accept "none" as an authentication
     * Call this method before auth()
     * @throws SshException
     */
    public function getAuthMethods(string $username): null|bool|array
    {
        if ($this->authMethods === null) {
            $this->authMethods = is_resource($this->session)
                ? ssh2_auth_none($this->session, $username)
                : throw new SshException("Not established ssh2 session");

            if (is_array($this->authMethods)) {
                $this->logger->notice("Authentication methods negotiated:", $this->getLogContext());
                $this->logger->debug(print_r($this->authMethods, true), $this->getLogContext());
            } else {
                $this->logger->warning("No authentication methods negotiated:", $this->getLogContext());
            }
        }

        return $this->authMethods;
    }

    /**
     * Authenticate as "none"
     * @throws SshException
     */
    public function authNone(string $username): self
    {
        return $this->authentication(new Auth\None($username));
    }

    /**
     * @throws SshException
     */
    public function authAgent(string $username): self
    {
        return $this->authentication(new Auth\Agent($username));
    }

    /**
     * Authenticate over SSH using a plain password
     *
     * @throws SshException
     */
    public function authPassword(string $username, string $password): self
    {
        return $this->authentication(new Auth\Password($username, $password));
    }

    /**
     * Authenticate using a public key
     *
     * @throws SshException
     */
    public function authPubkey(string $username, string $pubkeyFile, string $privkeyFile, string $passphrase): self
    {
        return $this->authentication(new Auth\Pubkey($username, $pubkeyFile, $privkeyFile, $passphrase));
    }

    /**
     * Authenticate using a public hostkey
     *
     * @throws SshException
     */
    public function authHostbased(
        string $username,
        string $hostname,
        string $pubkeyFile,
        string $privkeyFile,
        string $passphrase = '',
        string $localUsername = ''
    ): self
    {
        return $this->authentication(
            new Auth\Hostbased(
                $username,
                $hostname,
                $pubkeyFile,
                $privkeyFile,
                $passphrase,
                $localUsername
            )
        );
    }

    /**
     * Authenticate over SSH
     *
     * @throws SshException
     */
    public function authentication(AuthInterface $auth): self
    {
        $this->auth = $auth;

        if (!is_resource($this->session)) {
            $this->loggedException("Failed connecting");
        }
        // after authorization, this method will not work;
        $this->getAuthMethods($auth->getUsername());

        $this->logger->notice("Trying authenticate...", $this->getLogContext());

        $this->isAuthorised = $auth->authenticate($this->session);

        if (false === $this->isAuthorised) {
            $this->loggedException("Failed authentication");
        }

        $this->logger->notice("" . " authentication success", $this->getLogContext());

        return $this;
    }

    public function getAuth(): ?AuthInterface
    {
        return $this->auth;
    }

    public function isAuthorised(): bool
    {
        return $this->isAuthorised;
    }

    public function __get(string $name)
    {
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (property_exists($this->configuration, $name)) {
            /** @psalm-var mixed $value */
            $value = $this->configuration->get($name);
            if ($value instanceof TypeEnumInterface) {
                return $value->getValue();
            }

            return $value;
        }

        throw new SshException('Getting unknown property: ' . $this::class . '::' . $name);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('host %s:%s', $this->context['{host}'], $this->context['{port}']);
    }

    /**
     * @psalm-return array 'array{'{encoding}': non-falsy-string, '{height}': numeric-string, '{host}': non-empty-string, '{port}': numeric-string, '{pty}': "disabled"|mixed, '{termType}': string, '{timeout}': numeric-string, '{wait}': numeric-string, '{widthHeightType}': "TERM_UNIT_CHARS"|"TERM_UNIT_PIXELS", '{width}': numeric-string}
     */
    #[ArrayShape([
        '{host}' => "string",
        '{port}' => "string",
        '{wait}' => "string",
        '{timeout}' => "string",
        '{termType}' => "string",
        '{width}' => "string",
        '{height}' => "string",
        '{widthHeightType}' => "string",
        '{pty}' => "string"
    ])]
    /**
     * @psalm-return array<string, string>
     */
    public function getLogContext(): array
    {
        return $this->context;
    }

    /**
     * @psalm-suppress MixedAssignment, MixedArgumentTypeCoercion
     */
    private function setContext(string $host, int $port): void
    {
        $this->context['{host}'] = $host;
        $this->context['{port}'] = (string) $port;
        foreach ($this->configuration->getAsArray() as $key => $value) {
            $this->context['{' . $key . '}'] = is_array($value)
                ? implode(',' . PHP_EOL, $value)
                : (string) $value;
        }

        $this->logger->info("DEBUG mode is ON", $this->context);
        $this->logger->info("LOGGING start", $this->context);
        $this->logger->info(
            "{property} set to {value} seconds",
            $this->context + ['{property}' => 'TIMEOUT', '{value}' => (string) $this->timeout]
        );
        $this->logger->info(
            "{property} set to {value} microseconds",
            $this->context + ['{property}' => 'WAIT', '{value}' => (string) $this->wait]
        );
    }

    /**
     * @throws SshException
     */
    public function loggedException(string $message, array $context = []): never
    {
        $this->logger->critical($message, $this->context + $context);
        throw new SshException($message);
    }

    /**
     * Destructor. Cleans up socket connection and command buffer.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
    }

}