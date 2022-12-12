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
use Stringable;
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
 * $conf = (new Configuration())
 *  ->setTermType('dumb');
 *
 * $ssh2 = new Ssh($conf);
 * $ssh2->connect('192.168.1.1', 22, new PrintableLogger())
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
 */
final class Ssh implements SshInterface
{
    private string $host = 'localhost';
    private int $port = 22;
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
    private array $context = [
        '{host}' => 'localhost',
        '{port}' => '22'
    ];

    public function __construct(
        public Configuration   $configuration = new Configuration(),
        public LoggerInterface $logger = new NullLogger()
    )
    {
        if (!function_exists('ssh2_connect')) {
            throw new SshException("ssh2_connect function doesn't exist! Please install \"ext-ssh2\" php module.");
        }

        register_shutdown_function([$this, 'disconnect']);
    }

    /**
     * @psalm-suppress PossiblyNullArgument
     */
    public function connect(string $host = 'localhost', int $port = 22, ?LoggerInterface $logger = null): SshInterface
    {
        $new = clone $this;

        $new->host = $host;
        $new->port = $port;

        $new->setLogger($logger ?? $this->logger);

        $new->setContext();

        $new->logger->notice("Trying connection...", $new->getLogContext());

        $new->session = ssh2_connect(
            $host,
            $port,
            $new->configuration->getMethods(),
            $new->configuration->getCallbacks()
        );

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
                $this->logger->notice("Authentication methods negotiated for username \"$username\":", $this->getLogContext());
                $this->logger->debug(print_r($this->authMethods, true), $this->getLogContext());
            } else {
                $this->logger->warning("No authentication methods negotiated for username \"$username\":", $this->getLogContext());
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
        return $this->setAuth(new Auth\None($username));
    }

    /**
     * @throws SshException
     */
    public function authAgent(string $username): self
    {
        return $this->setAuth(new Auth\Agent($username));
    }

    /**
     * Authenticate over SSH using a plain password
     *
     * @throws SshException
     */
    public function authPassword(string $username, string $password): self
    {
        return $this->setAuth(new Auth\Password($username, $password));
    }

    /**
     * Authenticate using a public key
     *
     * @throws SshException
     */
    public function authPubkey(string $username, string $pubkeyFile, string $privkeyFile, string $passphrase): self
    {
        return $this->setAuth(new Auth\Pubkey($username, $pubkeyFile, $privkeyFile, $passphrase));
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
        return $this->setAuth(
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

    public function authenticate(): self
    {
        if ($this->auth === null) {
            $this->loggedException("Do not implements AuthInterface");
        }

        if (!is_resource($this->session)) {
            $this->loggedException("Failed connecting");
        }

        $this->getAuthMethods($this->auth->getusername());

        $this->logger->notice("Trying authenticate...", $this->getLogContext());

        $this->isAuthorised = $this->auth->authenticate($this->session);
        if (false === $this->isAuthorised) {
            $this->loggedException("Failed authentication");
        }

        $this->logger->notice($this->auth::class . " authentication success", $this->getLogContext());

        return $this;
    }

    public function getAuth(): ?AuthInterface
    {
        return $this->auth;
    }

    public function setAuth(?AuthInterface $auth = null): self
    {
        $this->auth = $auth;

        return $this;
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

        throw new SshException(sprintf('Getting unknown property: %s::%s', self::class, $name));
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
    private function setContext(): void
    {
        $this->context['{host}'] = $this->host;
        $this->context['{port}'] = (string) $this->port;
        foreach ($this->configuration->getAsArray() as $key => $value) {
            if (is_string($value)) {
                $this->context['{' . $key . '}'] = $value;
            } elseif (is_array($value)) {
                $this->context['{' . $key . '}'] = implode(',' . PHP_EOL, $value);
            } elseif ($value instanceof Stringable || is_numeric($value)) {
                $this->context['{' . $key . '}'] = (string) $value;
            }
        }

        $this->logger->info("DEBUG mode is ON", $this->context);
        $this->logger->info("LOGGING start", $this->context);
        $this->logger->info(
            "{property} set to {value} seconds",
            $this->context + ['{property}' => 'TIMEOUT', '{value}' => (string) $this->configuration->getTimeout()]
        );
        $this->logger->info(
            "{property} set to {value} microseconds",
            $this->context + ['{property}' => 'WAIT', '{value}' => (string) $this->configuration->getWait()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $this->context + ['{property}' => 'TERMTYPE', '{value}' => $this->configuration->getTermType()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $this->context + ['{property}' => 'WIDTH', '{value}' => (string) $this->configuration->getWidth()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $this->context + ['{property}' => 'HEIGHT', '{value}' => (string) $this->configuration->getHeight()]
        );
        $this->logger->info(
            "{property} set to {value}",
            $this->context + ['{property}' => 'WIDTHHEIGHTTYPE', '{value}' => $this->configuration->getWidthHeightTypeEnum()->name],
        );
        if ($this->configuration->getEnv() === null) {
            $this->logger->info(
                "{property} set to {value}",
                $this->context + ['{property}' => 'ENV', '{value}' => 'NULL']
            );
        } elseif (is_array($this->configuration->getEnv())) {
            /**
             * @psalm-var string $key
             * @psalm-var string $value
             */
            foreach ($this->configuration->getEnv() as $key => $value) {
                $this->logger->info(
                    "{property} set to {value}",
                    $this->context + ['{property}' => 'ENV', '{value}' => $key . ' => ' . $value]
                );
            }
        }
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