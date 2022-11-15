<?php declare(strict_types=1);

namespace jzfpost\ssh2;

/**
 * SSH2 helper class.
 *
 * PHP version ^8.1
 *
 * @category  Net
 * @requires ext-ssh2
 * @version 0.3.0
 *
 * @license   MIT
 *
 * @link      http://www.php.net/manual/en/book.ssh2.php
 */
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exception\Ssh2Exception;
use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Shell\Shell;
use Psr\Log\LoggerTrait;

use function function_exists;
use function is_resource;
use function strlen;


/**
 * Class PhpSsh2
 * @package jzfpost\ssh2
 *
 * USAGE:
 * ```php
 * $conf = new Configuration()->setHost('192.168.1.1')
 *  ->setLoggingFileName("/var/log/ssh2/log.txt")
 *  ->setDebugMode();
 *
 * $phpSsh2 = new PhpSsh2($conf);
 * $phpSsh2->connect($host)
 *        ->authPassword($username, $password)
 *        ->openShell(PhpSsh2::PROMPT_LINUX, 'xterm');
 * $result = $phpSsh2->send('ls -a', PhpSsh2::PROMPT_LINUX);
 * $phpSsh2->disconnect();
 * ```
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
 *
 */
class PhpSsh2 implements Ssh2Interface
{
    use LoggerTrait;

    /**
     * Commands turn off pagination on terminal
     */
    public const TERMINAL_PAGINATION_OFF_CISCO = 'terminal length 0';
    public const TERMINAL_PAGINATION_OFF_HUAWEI = 'screen-length 0 temporary';

    protected Configuration $configuration;

    public AuthInterface $auth;
    public bool $isAuthorised = false;

    /**
     * @var resource|closed-resource|false the SSH2 resource
     */
    protected mixed $sshConnection = false;
    /**
     * @var resource|closed-resource|false the shell emulator
     */
    protected mixed $shell = false;
    /**
     * @var string Remote user name.
     */
    protected string $username = 'username';
    /**
     * @var float|null Command Execute timestamp
     */
    public ?float $executeTimestamp = null;

    public function __construct(Configuration $configuration = new Configuration())
    {
        $this->configuration = $configuration;

        if (!function_exists('ssh2_connect')) {
            throw new Ssh2Exception("ssh2_connect function doesn't exist! Please install \"ext-ssh2\" php module.");
        }

        $this->info($configuration->isDebugMode() ? "DEBUG mode is ON" : "DEBUG mode is OFF");
        $this->info($this->loggingFileName ? "LOGGING start to file '{value}'" : 'LOGGING set to OFF', ['{value}' => $this->loggingFileName]);
        $this->info("{property} set to {value} seconds", ['{property}' => 'TIMEOUT', '{value}' => (string) $this->timeout]);
        $this->info("{property} set to {value} microseconds", ['{property}' => 'WAIT', '{value}' => (string) $this->wait]);
        $this->info($this->encoding ? "{property} set to '{value}'" : "{property} set to OFF",
            [
                '{property}' => 'ENCODING',
                '{value}' => $this->encoding
            ]
        );

        register_shutdown_function(array($this, 'disconnect'));
    }

    public function connect(): self
    {
        if ($this->isConnected()) {
            $this->disconnect();
            $this->critical("Connection already exists on host {host}:{port}");
            throw new Ssh2Exception(sprintf("Connection already exists on %s:%d", $this->host, $this->port));
        }

        $this->info('Trying connection to host {host}:{port}');

        $this->sshConnection = @ssh2_connect($this->host, $this->port, $this->methods, $this->callbacks);

        if (!$this->isConnected()) {
            $this->critical("Connection refused to host {host}:{port}");
            throw new Ssh2Exception(sprintf("Connection refused to host %s:%d", $this->host, $this->port));
        }

        $this->info('Connection established success to host {host}:{port}');

        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return is_resource($this->sshConnection);
    }

    /**
     * @return bool
     */
    public function isShellOpened(): bool
    {
        return is_resource($this->shell);
    }

    /**
     *
     * @return false|string
     */
    public function getErrors(): false|string
    {
        return $this->shell->getErrors();
    }

    #[Pure] public function getShell(): Shell
    {
        return is_resource($this->shell) ? $this->shell : $this->shell = new Shell($this);
    }

    /**
     * Closes SSH socket.
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->getShell()->close();
//            ssh2_disconnect($this->sshConnection);
//            if (@ssh2_disconnect($this->sshConnection)) {
                $this->shell = false;
                $this->isAuthorised = false;
                $this->sshConnection = false;
 //               $this->info('Disconnect completed');
 //           } else {
 //               $this->critical('Disconnection fail');
//            }
        }
    }

    /**
     * Opens a shell over SSH for us to send commands and receive responses from.
     *
     * @param string $prompt
     * @param string $termType The Terminal Type we will be using
     * @param array $env Name/Value array of environment variables to set. If array empty, segmentation fault will provide
     * @param positive-int $width Width of the terminal
     * @param positive-int $height Height of the terminal
     * @param int $width_height_type
     * @return self
     * @throws Ssh2Exception
     */
    /*
    public function openShell(string $prompt, string $termType = 'dumb', array $env = [null], int $width = 240, int $height = 240, int $width_height_type = SSH2_TERM_UNIT_CHARS): self
    {
        if (is_resource($this->shell)) {
            throw new Ssh2Exception("Already opened shell at $this->host:$this->port connection");
        }
        if (false === $this->isConnected()) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new Ssh2Exception("Failed connecting to host $this->host:$this->port");
        }
        if (false === $this->isAuthorised) {
            $this->critical("Failed authorisation on host {host}:{port}");
            throw new Ssh2Exception("Failed authorisation on host $this->host:$this->port");
        }

        $this->info('Trying opening shell at {host}:{port} connection');

        $this->term_type = $termType;
        $this->width = $width;
        $this->height = $height;
        $this->width_height_type = $width_height_type;

        if (is_resource($this->sshConnection)) {
            $this->shell = @ssh2_shell(
                $this->sshConnection,
                $this->term_type,
                $this->env,
                $this->width,
                $this->height,
                $this->width_height_type
            );

            if (is_resource($this->shell)) {

                $this->info('Shell opened success at {host}:{port} connection');

                $this->errors = @ssh2_fetch_stream($this->shell, SSH2_STREAM_STDERR);

                if (false === @stream_set_blocking($this->shell, true)) {
                    $this->critical("Unable to set blocking shell at {host}:{port} connection");
                    throw new Ssh2Exception("Unable to set blocking shell at $this->host:$this->port connection");
                }

                $this->readTo($prompt);
                $this->clearBuffer();

                return $this;
            }
        }
        $this->critical("Unable to establish shell at {host}:{port} connection");
        throw new Ssh2Exception("Unable to establish shell at $this->host:$this->port connection");
    }
    */

    /**
     * @return self
     */
    /*
    public function closeShell(): self
    {
        if (is_resource($this->shell)) {
            if (!@fclose($this->shell)) {
                $this->critical('Shell stream closes is fail.');
            }
        }

        if (is_resource($this->errors)) {
            if (!@fclose($this->errors)) {
                $this->critical('Errors stream closes is fail.');
            }
        }

        $this->shell = false;
        $this->errors = false;
        return $this;
    }
     */

    /**
     * Clears internal command buffer.
     *
     * @return void
     */
    /*
    private function clearBuffer(): void
    {
        $this->buffer = '';
    }
    */

    /**
     * Reads characters from the shell and adds them to command buffer.
     * Handles telnet control characters. Stops when prompt is encountered.
     *
     * @param string $prompt
     * @return void
     * @throws Ssh2Exception
     *
     */
    /*
    private function readTo(string $prompt): void
    {
        $this->prompt = str_replace('{username}', $this->username, $prompt);

        $this->info('Set prompt to "{prompt}"', ['{prompt}' => $prompt]);

        if (false === $this->isConnected()) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new Ssh2Exception("Failed connecting to host $this->host:$this->port");
        }

        if (!is_resource($this->shell)) {
            $this->critical("Unable to establish shell at {host}:{port} connection");
            throw new Ssh2Exception("Unable to establish shell at $this->host:$this->port connection");
        }

        $this->clearBuffer();
        usleep($this->wait);
        $time = time() + $this->timeout;
        do {
            $c = @fgetc($this->shell);
            if (false === $c) {
                $this->info("Couldn't find the requested : '" . $this->prompt . "', it was not in the data returned from server : '" . $this->buffer . "'");
                throw new Ssh2Exception("Couldn't find the requested : '" . $this->prompt . "', it was not in the data returned from server : '" . $this->buffer . "'");
            }

            // IANA TELNET OPTIONS
            if ($this->negotiateTelnetOptions($c)) {
                continue;
            }

            if ($this->encoding) {
                $c = mb_convert_encoding($c, $this->encoding);
            }

            $this->buffer .= $c;

//            $this->log('none', $c);

            if (preg_match("/$this->prompt\s?$/i", $this->buffer)) {
                $this->log('none', PHP_EOL);
                if (is_float($this->executeTimestamp)) {
                    $this->info("Command execution time is {timestamp} msec", ['{timestamp}' => (string)(microtime(true) - $this->executeTimestamp)]);
                }
                $this->history .= $this->buffer;
                $this->debug($this->buffer);
                break;
            }

            if ($time < time()) {
                $this->log('none', PHP_EOL);
                $this->history .= $this->buffer;
                $this->debug($this->buffer);
                $this->info("Timeout release before the prompt was read");
                break;
            }
        } while ($c !== $this->_NULL || $c !== $this->_DC1);

        $this->info("Data transmission is over");
    }
    */

    /**
     * Get the full History of the shell session.
     * @return string
     */
    /*
    public function getHistory(): string
    {
        return $this->history;
    }
    */

    /**
     * Telnet control character magic.
     *
     * @param string $c
     * @return bool
     */
    /*
    private function negotiateTelnetOptions(string $c): bool
    {
        switch ($c) {
            case $this->_IAC:
                $this->debug("_IAC command was received");
                return true;
            case $this->_DO:
                $this->debug("_DO command was received");
                break;
            case $this->_DONT:
                $this->debug("_DONT command was received");
                break;
            case $this->_WILL:
                $this->debug("_WILL command was received");
                break;
            case $this->_WONT:
                $this->debug("_WONT command was received");
                break;
            case $this->_ESC:
                $this->debug("_ESC command was received");
                break;
            default:
                return false;
        }
        if (is_resource($this->shell)) {
            $opt = fgetc($this->shell);
            $this->debug("Shell option: " . $opt);
        }
        return true;
    }
    */

    /**
     * Write command to a socket.
     *
     * @param string $cmd Stuff to write to socket
     * @return void
     * @throws Ssh2Exception
     */
    /*
    public function write(string $cmd): void
    {
        $host = $this->configuration->getHost();
        $port = $this->configuration->getPort();

        $context = [
            '{cmd}' => $cmd
        ];

        if (!is_resource($this->shell)) {
            $this->critical("Unable to establish shell at {host}:{port} connection");
            throw new Ssh2Exception("Unable to establish shell at $host:$port connection");
        }

        $this->clearBuffer();

        $this->info('Write command to host {host}:{port} => "{cmd}"', $context);
        $this->executeTimestamp = microtime(true);
        if ((!fwrite($this->shell, trim($cmd) . PHP_EOL)) < 0) {
            $this->critical("Error writing to shell at {host}:{port} connection");
            throw new Ssh2Exception("Error writing to shell at $host:$port connection");
        }
    }
    */

    /**
     * Write a command to shell and returns the results.
     * Command and promt will cut from result
     *
     * @param string $cmd Command we want to execute.
     * @param string $prompt
     *
     * @return string Command Results
     * @throws Ssh2Exception
     */
    /*
    public function send(string $cmd, string $prompt): string
    {
        $this->write($cmd);
        $this->readTo($prompt);

        $buffer = $this->trimFirstLine(trim($this->buffer));
        $buffer = $this->trimPrompt($buffer, $this->prompt);

        return utf8_encode($buffer);
    }
    */

    /**
     * Trim the first line of multiline text
     * @param string $text
     * @return string
     */
    /*
    public function trimFirstLine(string $text): string
    {
        return substr($text, (int)strpos($text, PHP_EOL, 1) + 1);
    }
    */

    /**
     * Trim the prompt string of multiline text
     * @param string $text
     * @param string $prompt
     * @return string
     */
    /*
    public function trimPrompt(string $text, string $prompt): string
    {
        return preg_replace("/$prompt\s*$/i", '', $text);
    }
    */

    public function getConnection(): mixed
    {
        return $this->sshConnection;
    }

    /**
     * Return shell if it was opened
     * @return false|resource
     */
    /*
    public function getShell()
    {
        return is_resource($this->shell) ? $this->shell : false;
    }
    */

    /**
     * Return list of negotiated methods
     * @return array
     */
    public function getMethodNegotiated(): array
    {
        return is_resource($this->sshConnection) ? @ssh2_methods_negotiated($this->sshConnection) : [];
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
     * @throws Ssh2Exception
     */
    public function getFingerprint(int $flags = 0): string
    {
        return is_resource($this->sshConnection)
            ? @ssh2_fingerprint($this->sshConnection, $flags)
            : throw new Ssh2Exception("Not established ssh2 session");;
    }

    /**
     * Returns the content of the command buffer.
     *
     * @return string Content of the command buffer
     */
    /*
    public function getBuffer(): string
    {
        return $this->buffer;
    }
    */

    /**
     * Return an array of accepted authentication methods.
     * Return true if the server does accept "none" as an authentication
     * Call this method before auth
     * @param string $username
     * @return array|bool
     * @throws Ssh2Exception
     */
    public function getAuthMethods(string $username): bool|array
    {
        return is_resource($this->sshConnection)
            ? @ssh2_auth_none($this->sshConnection, $username)
            : throw new Ssh2Exception("Not established ssh2 session");;
    }

    /**
     * Authenticate as "none"
     * @param string $username Remote user name.
     * @return self
     * @throws Ssh2Exception
     */
    public function authNone(string $username): self
    {
        return $this->auth(new Auth\None($username));
    }

    /**
     * @param string $username
     * @return self
     * @throws Ssh2Exception
     */
    public function authAgent(string $username): self
    {
        return $this->auth(new Auth\Agent($username));
    }

    /**
     * Authenticate over SSH using a plain password
     * @param string $username
     * @param string $password
     * @return self
     * @throws Ssh2Exception
     */
    public function authPassword(string $username, string $password): self
    {
        return $this->auth(new Auth\Password($username, $password));
    }

    /**
     * Authenticate using a public key
     * @param string $username
     * @param string $pubkeyFile
     * @param string $privkeyFile
     * @param string $passphrase If privkeyfile is encrypted (which it should be), the passphrase must be provided.
     * @return self
     * @throws Ssh2Exception
     */
    public function authPubkey(string $username, string $pubkeyFile, string $privkeyFile, string $passphrase): self
    {
        return $this->auth(new Auth\Pubkey($username, $pubkeyFile, $privkeyFile, $passphrase));
    }

    /**
     * Authenticate using a public hostkey
     * @param string $username
     * @param string $hostname
     * @param string $pubkeyFile
     * @param string $privkeyFile
     * @param string $passphrase If privkeyfile is encrypted (which it should be), the passphrase must be provided.
     * @param string $localUsername
     * @return self
     * @throws Ssh2Exception
     */
    public function authHostbased(string $username, string $hostname, string $pubkeyFile, string $privkeyFile, string $passphrase = '', string $localUsername = ''): self
    {
        return $this->auth(new Auth\Hostbased($username, $pubkeyFile, $privkeyFile, $passphrase, $localUsername));
    }

    /**
     * Authenticate over SSH
     * @param AuthInterface $auth
     * @return self
     * @throws Ssh2Exception
     */
    public function auth(AuthInterface $auth): self
    {
        $this->auth = $auth;

        $host = $this->configuration->getHost();
        $port = $this->configuration->getPort();
        if (!is_resource($this->sshConnection)) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new Ssh2Exception("Failed connecting to host $host:$port");
        }

        $this->isAuthorised = $auth->authenticate($this->sshConnection);
//        $this->username = $auth->getUsername();

        if (false === $this->isAuthorised) {
            $this->critical("Failed authentication on host {host}:{port}");
            throw new Ssh2Exception("Failed authentication on host $host:$port");
        }
        $this->info("Authentication success on host $host:$port");

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
                . '----------------' . PHP_EOL
                . $message
                . PHP_EOL
                . '----------------' . PHP_EOL;
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
    #[ArrayShape(['{host}' => "string", '{port}' => "string", '{wait}' => "string", '{timeout}' => "string"])]
    public function getLogContext(): array
    {
        return [
            '{host}' => $this->host,
            '{port}' => (string) $this->port,
            '{wait}' => (string) $this->wait,
            '{timeout}' => (string) $this->timeout,
        ];
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    public function getUsername(): string
    {
        if (isset($this->auth)) {
            return $this->auth->getUsername();
        }
        throw new Ssh2Exception("Not implemented username yet");
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
            return $this->configuration->$getter();
        }

        throw new Ssh2Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    public function __toString(): string
    {
        return $this->getUsername() . '@' . $this->host . ':' . $this->port;
    }
}
