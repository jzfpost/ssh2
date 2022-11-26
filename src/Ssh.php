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

use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Conf\TypeInterface;
use jzfpost\ssh2\Exceptions\SshException;
use jzfpost\ssh2\Exec\Exec;
use jzfpost\ssh2\Exec\ExecInterface;
use jzfpost\ssh2\Exec\Shell;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function function_exists;
use function register_shutdown_function;
use function is_resource;
use function fclose;
use function ssh2_connect;
use function ssh2_disconnect;
use function ssh2_methods_negotiated;
use function ssh2_fingerprint;
use function ssh2_auth_none;
use function ucfirst;
use function get_class;

/**
 *
 * USAGE:
 * ```php
 * $conf = (new Configuration('192.168.1.1'))
 *  ->setTermType('xterm');
 *
 * $ssh2 = new Ssh($conf);
 * $ssh2->connect()
 *  ->authPassword($username, $password);
 *
 * $shell = $ssh2->getShell()
 *  ->open(Shell::PROMPT_LINUX);
 *
 * $result = $shell->send('ls -a', Shell::PROMPT_LINUX);
 *
 * $ssh2->disconnect();
 * ```
 *
 * @property-read non-empty-string $host
 * @property-read positive-int $port
 * @property-read positive-int $timeout
 * @property-read positive-int $wait
 * @property-read string|false $loggingFileName
 * @property-read string|false $encoding
 * @property-read string $dateFormat
 * @property-read array $methods
 * @property-read array $callbacks
 * @property-read string|null $termType
 * @property-read array|null $env
 * @property-read int $width
 * @property-read int $height
 * @property-read int $widthHeightType
 * @property-read string|null $pty
 * @property-read array<string, string> $logContext;
 */
final class Ssh implements SshInterface, LoggerAwareInterface
{

    private bool $isAuthorised = false;
    private ?AuthInterface $auth = null;
    private readonly Configuration $configuration;
    private LoggerInterface $logger;
    /**
     * @var Exec|false
     */
    private mixed $exec = false;
    /**
     * @var resource|closed-resource|false
     */
    private mixed $session = false;

    private ?string $fingerPrint = null;
    private ?array $authMethods = null;
    private ?array $methodsNegotiated = null;

    public function __construct(Configuration $configuration = new Configuration(), LoggerInterface $logger = new NullLogger())
    {
        $this->configuration = $configuration;
        $this->logger = $logger;

        if (!function_exists('ssh2_connect')) {
            throw new SshException("ssh2_connect function doesn't exist! Please install \"ext-ssh2\" php module.");
        }

        $this->logger->info("DEBUG mode is ON", $this->getLogContext());
        $this->logger->info("LOGGING start", $this->getLogContext());
        $this->logger->info(
            "{property} set to {value} seconds",
            $this->getLogContext() + ['{property}' => 'TIMEOUT', '{value}' => (string) $this->timeout]
        );
        $this->logger->info(
            "{property} set to {value} microseconds",
            $this->getLogContext() + ['{property}' => 'WAIT', '{value}' => (string) $this->wait]
        );
        $this->logger->info($this->encoding ? "{property} set to '{value}'" : "{property} set to OFF",
            $this->getLogContext() + ['{property}' => 'ENCODING', '{value}' => $this->encoding]
        );

        register_shutdown_function(array($this, 'disconnect'));
    }

    public function connect(): SshInterface
    {
        if ($this->isConnected()) {
            $this->disconnect();
            $this->logger->critical("Connection already exists on host {host}:{port}", $this->getLogContext());
            throw new SshException("Connection already exists on $this");
        }

        $this->logger->notice('Trying connection to host {host}:{port}', $this->getLogContext());

        $this->session = @ssh2_connect($this->host, $this->port, $this->methods, $this->callbacks);

        if (!$this->isConnected()) {
            $this->logger->critical("Connection refused to host {host}:{port}", $this->getLogContext());
            throw new SshException("Connection refused to host $this");
        }

        $this->logger->notice('Connection established success to host {host}:{port}', $this->getLogContext());

        $this->getFingerPrint();
        $this->getMethodNegotiated();

        return $this;
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
            $this->exec = false;
        }

        $this->isAuthorised = false;

        if (is_resource($this->session) && @ssh2_disconnect($this->session)) {
            $this->session = false;
            $this->logger->notice('Disconnection complete', $this->getLogContext());
        }
    }

    public function getExec(): Exec
    {
        return $this->exec instanceof Exec ? $this->exec : $this->exec = new Exec($this, $this->logger);
    }

    public function getShell(): Shell
    {
        return $this->exec instanceof Shell ? $this->exec : $this->exec = new Shell($this, $this->logger);
    }

    public function getUsername(): string
    {
        if ($this->auth instanceof AuthInterface) {
            return $this->auth->getUsername();
        }
        throw new SshException("Not implemented username yet");
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
    public function getMethodNegotiated(): array
    {
        if ($this->methodsNegotiated === null) {
            $this->methodsNegotiated = is_resource($this->session) ? @ssh2_methods_negotiated($this->session) : [];

            if (is_array($this->methodsNegotiated)) {
                $this->logger->notice('Methods negotiated at {host}:{port}:', $this->logContext);
                $this->logger->debug(print_r($this->methodsNegotiated, true), $this->logContext);
            } else {
                $this->logger->warning('No methods negotiated at {host}:{port}:', $this->logContext);
            }
        }

        return $this->methodsNegotiated;
    }

    /**
     * Retrieve fingerprint of remote server
     * @param int $flags
     * flags may be either of
     * SSH2_FINGERPRINT_MD5 or
     * SSH2_FINGERPRINT_SHA1 logically ORed with
     * SSH2_FINGERPRINT_HEX or
     * SSH2_FINGERPRINT_RAW.
     * @return string the hostkey hash as a string
     * @throws SshException
     */
    public function getFingerPrint(int $flags = 0): string
    {
        if ($this->fingerPrint === null) {
            $this->fingerPrint = is_resource($this->session)
                ? @ssh2_fingerprint($this->session, $flags)
                : throw new SshException("Not established ssh2 session");

            $this->logger->notice('FingerPrint at {host}:{port}:', $this->logContext);
            $this->logger->debug($this->fingerPrint, $this->logContext);
        }

        return $this->fingerPrint;
    }

    /**
     * Return an array of accepted authentication methods.
     * Return true if the server does accept "none" as an authentication
     * Call this method before auth()
     * @param string $username
     * @return array|bool
     * @throws SshException
     */
    public function getAuthMethods(string $username): bool|array
    {
        if ($this->authMethods === null) {
            $this->authMethods = is_resource($this->session)
                ? @ssh2_auth_none($this->session, $username)
                : throw new SshException("Not established ssh2 session");

            if (is_array($this->authMethods)) {
                $this->logger->notice('Authentication methods negotiated at {host}:{port}:', $this->logContext);
                $this->logger->debug(print_r($this->authMethods, true), $this->logContext);
            } else {
                $this->logger->warning('No authentication methods negotiated at {host}:{port}:', $this->logContext);
            }
        }

        return $this->authMethods;
    }

    /**
     * Authenticate as "none"
     * @param string $username Remote user name.
     * @return self
     * @throws SshException
     */
    public function authNone(string $username): self
    {
        return $this->authentication(new Auth\None($username));
    }

    /**
     * @param string $username
     * @return self
     * @throws SshException
     */
    public function authAgent(string $username): self
    {
        return $this->authentication(new Auth\Agent($username));
    }

    /**
     * Authenticate over SSH using a plain password
     * @param string $username
     * @param string $password
     * @return self
     * @throws SshException
     */
    public function authPassword(string $username, string $password): self
    {
        return $this->authentication(new Auth\Password($username, $password));
    }

    /**
     * Authenticate using a public key
     * @param string $username
     * @param string $pubkeyFile
     * @param string $privkeyFile
     * @param string $passphrase If privkeyFile is encrypted (which it should be), the passphrase must be provided.
     * @return self
     * @throws SshException
     */
    public function authPubkey(string $username, string $pubkeyFile, string $privkeyFile, string $passphrase): self
    {
        return $this->authentication(new Auth\Pubkey($username, $pubkeyFile, $privkeyFile, $passphrase));
    }

    /**
     * Authenticate using a public hostkey
     * @param string $username
     * @param string $hostname
     * @param string $pubkeyFile
     * @param string $privkeyFile
     * @param string $passphrase If privkeyFile is encrypted (which it should be), the passphrase must be provided.
     * @param string $localUsername
     * @return self
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
     * @param AuthInterface $auth
     * @return self
     * @throws SshException
     */
    public function authentication(AuthInterface $auth): self
    {
        $this->auth = $auth;

        if (!is_resource($this->session)) {
            $this->logger->critical("Failed connecting to host {host}:{port}", $this->getLogContext());
            throw new SshException("Failed connecting to host $this");
        }
        $this->logger->notice("Trying authenticate on host $this");

        $this->getAuthMethods($auth->getUsername());

        $this->isAuthorised = $auth->authenticate($this->session);

        if (false === $this->isAuthorised) {
            $this->logger->critical("Failed authentication on host {host}:{port}", $this->getLogContext());
            throw new SshException("Failed authentication on host $this");
        }
        $this->logger->notice("Authentication success on host $this");

        return $this;
    }

    public function isAuthorised(): bool
    {
        return $this->isAuthorised;
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

    public function __get(string $name)
    {
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->configuration, $getter)) {
            /** @var mixed $value */
            $value = $this->configuration->$getter();
            if ($value instanceof TypeInterface) {
                return $value->getValue();
            }
            return $value;
        }

        throw new SshException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    public function __toString(): string
    {
        return $this->host . ':' . $this->port;
    }

    /**
     * @return array 'array{'{encoding}': non-falsy-string, '{height}': numeric-string, '{host}': non-empty-string, '{port}': numeric-string, '{pty}': "disabled"|mixed, '{termType}': string, '{timeout}': numeric-string, '{wait}': numeric-string, '{widthHeightType}': "TERM_UNIT_CHARS"|"TERM_UNIT_PIXELS", '{width}': numeric-string}
     */
    public function getLogContext(): array
    {
        return [
            '{host}' => $this->host,
            '{port}' => (string) $this->port,
            '{wait}' => (string) $this->wait,
            '{timeout}' => (string) $this->timeout,
            '{encoding}' => (string) $this->encoding ?: 'utf8',
            '{termType}' => (string) $this->termType,
            '{width}' => (string) $this->width,
            '{height}' => (string) $this->height,
            '{widthHeightType}' => $this->widthHeightType === SSH2_TERM_UNIT_CHARS ? 'TERM_UNIT_CHARS' : 'TERM_UNIT_PIXELS',
            '{pty}' => $this->pty ?? 'disabled',
        ];
    }
}