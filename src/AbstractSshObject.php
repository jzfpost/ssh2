<?php declare(strict_types=1);
/**
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://giathub/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 */

namespace jzfpost\ssh2;

use JetBrains\PhpStorm\ArrayShape;
use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exceptions\SshException;
use jzfpost\ssh2\Exec\Exec;
use jzfpost\ssh2\Shell\Shell;
use Psr\Log\LoggerTrait;

/**
 *
 * @property-read non-empty-string $host
 * @property-read positive-int $port
 * @property-read positive-int $timeout
 * @property-read positive-int $wait
 * @property-read string|false $loggingFileName
 * @property-read string|false $encoding
 * @property-read array $env
 * @property-read string $dateFormat
 * @property-read array $methods
 * @property-read array $callbacks
 */
abstract class AbstractSshObject implements SshInterface
{
    use LoggerTrait;

    public bool $isAuthorised = false;
    protected ?AuthInterface $auth = null;
    protected Configuration $configuration;
    /**
     * @var Tunnel|false
     */
    protected mixed $tunnel = false;
    /**
     * @var Exec|false
     */
    protected mixed $exec = false;
    /**
     * @var Shell|false
     */
    protected mixed $shell = false;

    /**
     * @var resource|closed-resource|false
     */
    protected mixed $session = false;

    abstract public function connect(): self;

    /**
     * @inheritDoc
     */
    public function getSession(): mixed
    {
        return $this->session;
    }

    public function isConnected(): bool
    {
        return is_resource($this->session);
    }

    public function disconnect(): void
    {
        $this->__destruct();
    }

    public function getTunnel(Configuration $configuration): Tunnel
    {
        return $this->tunnel instanceof Tunnel ? $this->tunnel : $this->tunnel = new Tunnel($this, $configuration);
    }

    public function getExec(): Exec
    {
        return $this->exec instanceof Exec ? $this->exec : $this->exec = new Exec($this);
    }

    public function getShell(): Shell
    {
        return $this->shell instanceof Shell ? $this->shell : $this->shell = new Shell($this);
    }

    public function getShellErrors(): false|string
    {
        return $this->getShell()->getStderr();
    }

    public function isShellOpened(): bool
    {
        return $this->getShell()->isOpened();
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

    /**
     * Return list of negotiated methods
     */
    public function getMethodNegotiated(): array
    {
        return is_resource($this->session) ? @ssh2_methods_negotiated($this->session) : [];
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
        return is_resource($this->session)
            ? @ssh2_fingerprint($this->session, $flags)
            : throw new SshException("Not established ssh2 session");
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
        return is_resource($this->session)
            ? @ssh2_auth_none($this->session, $username)
            : throw new SshException("Not established ssh2 session");
    }

    /**
     * Authenticate as "none"
     * @param string $username Remote user name.
     * @return self
     * @throws SshException
     */
    public function authNone(string $username): self
    {
        return $this->authentication(new Auth\None(...func_get_args()));
    }

    /**
     * @param string $username
     * @return self
     * @throws SshException
     */
    public function authAgent(string $username): self
    {
        return $this->authentication(new Auth\Agent(...func_get_args()));
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
        return $this->authentication(new Auth\Password(...func_get_args()));
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
        return $this->authentication(new Auth\Pubkey(...func_get_args()));
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
    public function authHostbased(string $username, string $hostname, string $pubkeyFile, string $privkeyFile, string $passphrase = '', string $localUsername = ''): self
    {
        return $this->authentication(new Auth\Hostbased(...func_get_args()));
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
            $this->critical("Failed connecting to host {host}:{port}");
            throw new SshException("Failed connecting to host $this");
        }

        $this->isAuthorised = $auth->authenticate($this->session);

        if (false === $this->isAuthorised) {
            $this->critical("Failed authentication on host {host}:{port}");
            throw new SshException("Failed authentication on host $this");
        }
        $this->info("Authentication success on host $this");

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array()): void
    {
        /** @psalm-var array<array-key, float|int|string> $context */

        $context = array_merge_recursive($this->getLogContext(), $context);

        if (!is_string($level)) {
            $level = (string)$level;
        }

        $timestamp = date($this->configuration->getDateFormat());

        $message = str_replace(array_keys($context), $context, $message);
        if (strlen($message) > 1) {
            $message = ucfirst($message);
        }

        if ($level === 'none') {
            $text = $message;
        } elseif ($level === 'debug') {
            $text = sprintf('[%s] %s %s:', $timestamp, $this->host, $level)
                . PHP_EOL
                . '---------------- ---------------- ----------------' . PHP_EOL
                . $message
                . PHP_EOL
                . '================ ================ ================' . PHP_EOL;
        } else {
            $text = sprintf('[%s] %s %s: %s', $timestamp, $this->host, $level, $message) . PHP_EOL;
        }
        if ($this->loggingFileName) {
            @file_put_contents($this->loggingFileName, $text, FILE_APPEND);
        }
        if ($this->configuration->isDebugMode()) {
            print $text;
        }
    }

    /**
     * @return array
     * @psalm-return array<array-key, string>
     */
    #[ArrayShape(['{host}' => "string", '{port}' => "string", '{wait}' => "string", '{timeout}' => "string", '{username}' => "string"])]
    private function getLogContext(): array
    {
        return [
            '{host}' => $this->host,
            '{port}' => (string)$this->port,
            '{wait}' => (string)$this->wait,
            '{timeout}' => (string)$this->timeout,
            '{username}' => $this->isAuthorised ? $this->getUsername() : 'user'
        ];
    }

    /**
     * Destructor. Cleans up socket connection and command buffer.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->isConnected()) {
            if ($this->shell instanceof Shell) {
                $this->shell->close();
                $this->shell = false;
            }
            if ($this->tunnel instanceof Tunnel) {
                $this->tunnel->disconnect();
            }
            if ($this->exec instanceof Exec) {
                $this->exec = false;
            }
            @ssh2_disconnect($this->session);  //This function may be throw Exception "Segmentation fault" in libssh2v1.8.0
            $this->isAuthorised = false;
            $this->session = false;
            $this->info('Disconnection complete');
        }
    }

    public function __get(string $name)
    {
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this->configuration, $getter)) {
            return $this->configuration->$getter();
        }

        throw new SshException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    public function __toString(): string
    {
        return $this->host . ':' . $this->port;
    }
}