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

namespace jzfpost\ssh2;

use jzfpost\ssh2\Auth\Authenticable;
use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Auth\MethodsNegotiator\AuthMethodsNegotiator;
use jzfpost\ssh2\Auth\MethodsNegotiator\AuthMethodsNegotiatorInterface;
use jzfpost\ssh2\Conf\Configurable;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Conf\FPAlgorithmEnum;
use jzfpost\ssh2\CryptMethodsNegotiator\CryptMethodsNegotiator;
use jzfpost\ssh2\CryptMethodsNegotiator\CryptMethodsNegotiatorInterface;
use jzfpost\ssh2\Exec\Exec;
use jzfpost\ssh2\Exec\ExecInterface;
use jzfpost\ssh2\Exec\Shell;
use jzfpost\ssh2\FingerPrintNegotiator\FingerPrintNegotiator;
use jzfpost\ssh2\FingerPrintNegotiator\FingerPrintNegotiatorInterface;
use jzfpost\ssh2\Session\Session;
use jzfpost\ssh2\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;
use UnitEnum;
use function function_exists;
use function print_r;
use function register_shutdown_function;
use function sprintf;
use function strtoupper;

/**
 *
 * USAGE:
 * ```php
 * $conf = (new Configuration())
 *  ->setTermType('dumb');
 *
 * $ssh2 = new Ssh($conf);
 * $ssh2->connect('192.168.1.1', 22, new RealtimeLogger())
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
final class Ssh implements SshInterface, Configurable, Stringable, Authenticable
{
    /**
     * @var non-empty-string
     */
    private string $host = 'localhost';
    /**
     * @var positive-int
     */
    private int $port = 22;
    private ?AuthInterface $auth = null;
    private ?ExecInterface $exec = null;
    private ?SessionInterface $session = null;
    private ?FingerPrintNegotiatorInterface $fingerPrint = null;
    private ?CryptMethodsNegotiatorInterface $cryptMethodsNegotiated = null;
    /**
     * @var AuthMethodsNegotiatorInterface[]
     */
    private array $authMethodsNegotiators = [];
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
            throw new SshException("ssh2_connect function doesn't exist! Please install \"ext-ssh2\" php module.", $this->logger);
        }

        register_shutdown_function([$this, 'disconnect']);
    }

    /**
     * @inheritDoc
     */
    public function connect(string $host = 'localhost', int $port = 22, ?LoggerInterface $logger = null): SshInterface
    {
        $new = clone $this;

        $new->host = $host;
        $new->port = $port;
        $new->logger = $logger ?? $new->logger;
        $new->setContext();

        $new->session = $new->getSession()->connect($new->host, $new->port);

        $new->getFingerPrint($new->configuration->getFingerPrintAlgorithmEnum());
        $new->getCryptMethodsNegotiated();

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getSession(): SessionInterface
    {
        return $this->session instanceof SessionInterface
            ? $this->session
            : $this->session = new Session($this->getConfiguration(), $this->logger, $this->context);
    }

    public function isConnected(): bool
    {
        return $this->getSession()->isConnected();
    }

    public function disconnect(): void
    {
        if ($this->exec instanceof ExecInterface) {
            $this->exec->close();
            $this->exec = null;
        }

        if ($this->session instanceof SessionInterface) {
            $this->session->disconnect();
            $this->session = null;
        }
    }

    /**
     * Retrieve fingerprint of remote server
     * @return string the hostkey hash as a string
     * @throws SshException
     */
    public function getFingerPrint(
        FPAlgorithmEnum                $algorithm = FPAlgorithmEnum::md5,
        FingerPrintNegotiatorInterface $fingerPrintNegotiator = new FingerPrintNegotiator()
    ): string
    {
        if ($this->fingerPrint === null) {
            $this->fingerPrint = $fingerPrintNegotiator->negotiate($this->getSession(), $algorithm);

            $this->logger->notice("Retrieve fingerPrint algorithm: $algorithm->name", $this->context);
            $this->logger->debug($this->fingerPrint->getFingerPrint(), $this->context);
        }

        return $this->fingerPrint->getFingerPrint();
    }

    /**
     * Return list of negotiated methods
     */
    public function getCryptMethodsNegotiated(
        CryptMethodsNegotiatorInterface $cryptMethodsNegotiator = new CryptMethodsNegotiator()
    ): array
    {
        if ($this->cryptMethodsNegotiated === null) {
            $this->cryptMethodsNegotiated = $cryptMethodsNegotiator->negotiate($this->getSession());


            if (!empty($this->cryptMethodsNegotiated->getCryptMethodsAsArray())) {
                $this->logger->notice("CryptMethods negotiated:", $this->context);
                $this->logger->debug(print_r($this->cryptMethodsNegotiated->getCryptMethodsAsArray(), true), $this->context);
            } else {
                $this->logger->warning("No methods negotiated:", $this->context);
            }
        }

        return $this->cryptMethodsNegotiated->getCryptMethodsAsArray();
    }

    public function getExec(): Exec
    {
        return $this->exec instanceof Exec
            ? $this->exec
            : $this->exec = new Exec($this->getSession(), $this->configuration, $this->logger, $this->context);
    }

    public function getShell(): Shell
    {
        return $this->exec instanceof Shell
            ? $this->exec
            : $this->exec = new Shell($this->getSession(), $this->configuration, $this->logger, $this->context);
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Return an array of accepted authentication methods.
     */
    public function getAcceptedAuthMethods(
        string                         $username,
        AuthMethodsNegotiatorInterface $authMethodsNegotiator = new AuthMethodsNegotiator()
    ): array
    {
        if (!$this->isAuthorised()) {

            $this->authMethodsNegotiators[$username] = $authMethodsNegotiator->negotiate($this->getSession(), $username);

            $methods = $this->authMethodsNegotiators[$username]->getAcceptedAuthMethods();
            if (!empty($methods)) {
                $this->logger->notice("Authentication methods negotiated for username \"$username\":", $this->context);
                $this->logger->debug(print_r($methods, true), $this->context);
            } else {
                $this->logger->warning("No authentication methods negotiated for username \"$username\":", $this->context);
            }
        }

        return array_key_exists($username, $this->authMethodsNegotiators) ? $this->authMethodsNegotiators[$username]->getAcceptedAuthMethods() : [];
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
            throw new SshException("Do not implements AuthInterface", $this->logger, $this->context);
        }

        if (!$this->isConnected()) {
            throw new SshException("Failed connecting", $this->logger, $this->context);
        }

        $this->logger->notice("Trying authenticate...", $this->context);

        if (!$this->auth->authenticate($this->getSession())) {
            throw new SshException("Failed authentication", $this->logger, $this->context);
        }

        $this->logger->notice($this->auth::class . " authentication success", $this->context);

        return $this;
    }

    public function getAuth(): ?AuthInterface
    {
        return $this->auth;
    }

    public function setAuth(AuthInterface $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    public function isAuthorised(): bool
    {
        return $this->auth instanceof AuthInterface && $this->auth->isAuthorised();
    }

    /**
     * @psalm-suppress MixedAssignment, MixedArgumentTypeCoercion
     */
    private function setContext(): void
    {
        $this->context['{host}'] = $this->host;
        $this->context['{port}'] = (string) $this->port;

        $this->logger->info("DEBUG mode is ON", $this->context);
        $this->logger->info("LOGGING start", $this->context);

        $conf = $this->configuration->getAsArray();
        foreach ($conf as $property => $value) {
            $message = "{property} set to \"{value}\"";

            if ($property === 'timeout') {
                $message .= ' seconds';
            }

            if ($property === 'wait') {
                $message .= ' microseconds';
            }

            if ($value instanceof UnitEnum) {
                $value = $value->name;
            }

            if ($value === null) {
                $value = 'NULL';
            }

            $this->context['{' . $property . '}'] = print_r($value, true);

            $this->logger->info(
                $message,
                $this->context + [
                    '{property}' => strtoupper($property),
                    '{value}' => print_r($value, true)
                ]
            );
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('host %s:%d', $this->host, $this->port);
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