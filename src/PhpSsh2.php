<?php

namespace jzfpost\ssh2;

/**
 * SSH2 driver class.
 *
 * PHP version ^7.1
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA
 *
 * @category  Net
 * @version 0.0.1
 *
 * @license   GNU/LGPL v2.1
 *
 * @link      http://www.php.net/manual/en/book.ssh2.php
 * @link      https://github.com/bubba-h57/PHP-SSH2
 */

use Psr\Log\LoggerTrait;

/**
 * Class PhpSsh2
 * @package jzfpost\ssh2
 *
 * USAGE:
 * ```php
 * $phpSsh2 = new PhpSsh2(['timeout' => 10, 'wait' => '3500', 'logging' => '/var/log/ssh2/log.txt', 'screenLogging' => true]);
 * $phpSsh2->connect($host)
 * 		->authPassword($username, $password)
 * 		->openShell(PhpSsh2::PROMPT_LINUX, 'xterm');
 * $result = $phpSsh2->send('ls -a', PhpSsh2::PROMPT_LINUX);
 * $phpSsh2->disconnect();
 * ```
 */
class PhpSsh2
{
	use LoggerTrait;

	/**
	 * RegExp prompts
	 */
	public const PROMPT_LINUX = '{username}@[^:]+:~\$';
	public const PROMPT_LINUX_SU = 'root@[^:]+:[^#]+#';
	public const PROMPT_CISCO = '[\w._-]+>';
	public const PROMPT_CISCO_EN = '[\w._-]+#';
	public const PROMPT_HUAWEI = '<~?[\w._-]+>';
	public const PROMPT_HUAWEI_SY = '[~?[\w._-]+]';

    /**
     * Commands turn off pagination on terminal
     */
	public const TERMINAL_PAGINATION_OFF_CISCO = 'terminal length 0';
	public const TERMINAL_PAGINATION_OFF_HUAWEI = 'screen-length 0 temporary';

	/**
	 * @var bool
	 */
	public $isAuthorised = false;
	/**
	 * @var string the received data buffer
	 */
	protected $buffer;
	/**
	 * @var null|array
	 */
	protected $env = null;
	/**
	 * @var resource|false errors
	 */
	protected $errors = false;
	/**
	 * @var string
	 */
	protected $history = '';
	/**
	 * @var string URL or IP-address
	 */
	protected $host = 'localhost';
	/**
	 * @var int the TCP port to connection
	 */
	protected $port = 22;
	/**
	 * @var string shell prompt
	 */
	protected $prompt = '~$';
	/**
	 * @var resource|false shell
	 */
	protected $shell = false;
	/**
	 * @var resource|false the SSH2 resource
	 */
	protected $ssh2Connection = false;
	/**
	 * We use the dumb terminal to avoid excessive escape characters in windows SSH sessions.
	 * @var string
	 */
	protected $term_type = 'dumb';
	/**
	 * @var int the response timeout in seconds (s)
	 */
	protected $timeout = 10;
	/**
	 * @var string Remote user name.
	 */
	protected $username;
	/**
	 * Delay execution in micro seconds (ms)
	 * @var int
	 */
	protected $wait = 500;
	/**
	 * @var int Width of the virtual terminal
	 */
	protected $width = 240;
	/**
	 * @var int Height of the virtual terminal
	 */
	protected $height = 40;
	/**
	 * @var string|false Encoding characters
	 */
	protected $encoding = false;
	/**
	 * should be one of SSH2_TERM_UNIT_CHARS or SSH2_TERM_UNIT_PIXELS
	 * @var int SSH2_TERM_UNIT_CHARS|SSH2_TERM_UNIT_PIXELS
	 */
	protected $width_height_type = SSH2_TERM_UNIT_CHARS;
	/**
	 * @var string|bool file path for logging
	 */
	protected $logging = false;
	/**
	 * @var bool Print logs
	 */
	protected $screenLogging = true;
	/**
	 * @var float Command Execute timestamp
	 */
	protected $executeTimestamp;

	/**
	 * These are telnet options characters that might be of use for us.
	 */
	protected $_NULL;
	protected $_DC1;
	protected $_WILL;
	protected $_WONT;
	protected $_DO;
	protected $_DONT;
	protected $_IAC;
	protected $_ESC;

	/**
	 * Constructor.
	 *
	 * @param array $options
	 *
	 * @throws Ssh2Exception
	 */
	public function __construct(array $options = [])
	{
		if (!function_exists('ssh2_connect')) {
			throw new Ssh2Exception("ssh2_connect function doesn't exist! Need install \"ext-ssh2\" php module.");
		}

		$this->info('Logging start');

		if (isset($options[ 'host' ])) {
			$this->host = $options[ 'host' ];
			$this->info("{property} set to {value}", [ '{property}' => 'host', '{value}' => $this->host ]);
		}
		if (isset($options[ 'port' ])) {
			$this->host = $options[ 'port' ];
			$this->info("{property} set to {value}", [ '{property}' => 'port', '{value}' => $this->port ]);
		}
		if (isset($options[ 'logging' ])) {
			$this->logging = $options[ 'logging' ];
			$this->info("{property} set to {value}",
				[ '{property}' => 'logging', '{value}' => $this->logging === false ? 'disabled' : $this->logging ]);
		}
		if (isset($options[ 'screenLogging' ])) {
			$this->screenLogging = $options[ 'screenLogging' ];
			$this->info("{property} set to {value}",
				[ '{property}' => 'screenLogging', '{value}' => $this->screenLogging ? 'enabled' : 'disabled' ]);
		}
		if (isset($options[ 'timeout' ])) {
			$this->timeout = $options[ 'timeout' ];
			$this->info("{property} set to {value} seconds",
				[ '{property}' => 'timeout', '{value}' => $this->timeout ]);
		}
		if (isset($options[ 'wait' ])) {
			$this->wait = $options[ 'wait' ];
			$this->info("{property} set to {value} microseconds", [ '{property}' => 'wait', '{value}' => $this->wait ]);
		}
		if (isset($options[ 'encoding' ])) {
			$this->encoding = $options[ 'encoding' ];
			$this->info("{property} set to {value} microseconds",
				[ '{property}' => 'encoding', '{value}' => $this->encoding ]);
		}

		$this->_NULL = chr(0);
		$this->_DC1 = chr(17);
		$this->_WILL = chr(251);
		$this->_WONT = chr(252);
		$this->_DO = chr(253);
		$this->_DONT = chr(254);
		$this->_IAC = chr(255);
		$this->_ESC = chr(27);
	}

	/**
	 * Attempts connection to remote host.
	 *
	 * @param string $host Host name or IP address
	 * @param int $port [optional] the TCP port to connection
	 * @param array $methods [optional] Methods may be an associative array with any of the ssh2 connect parameters
	 * $methods = [
	 *     'kex' => 'diffie-hellman-group1-sha1, diffie-hellman-group14-sha1, diffie-hellman-group-exchange-sha1',
	 *     'hostkey' => 'ssh-rsa, ssh-dss',
	 *     'client_to_server' => [
	 *         'crypt' => 'rijndael-cbc@lysator.liu.se, aes256-cbc, aes192-cbc, aes128-cbc, 3des-cbc, blowfish-cbc, cast128-cbc, arcfour',
	 *         'comp' => 'zlib|none',
	 *         'mac' => 'hmac-sha1, hmac-sha1-96, hmac-ripemd160, hmac-ripemd160@openssh.com'
	 *      ]
	 *     'server_to_client' => [
	 *         'crypt' => 'rijndael-cbc@lysator.liu.se, aes256-cbc, aes192-cbc, aes128-cbc, 3des-cbc, blowfish-cbc, cast128-cbc, arcfour',
	 *         'comp' => 'zlib|none',
	 *         'mac' => 'hmac-sha1, hmac-sha1-96, hmac-ripemd160, hmac-ripemd160@openssh.com'
	 *     ]
	 * ]
	 * @param array $callbacks [optional] May be an associative array with any of the ssh2 connect parameters
	 * $callbacks = [
	 *     'ignore' => 'self::ignore_cb($message)',
	 *     'debug' => 'self::debug_cb($message, $language, $always_display)',
	 *     'macerror' => 'self::macerror_cb($packet)', //function must return bool
	 *     'disconnect' => 'self::disconnect_cb($reason, $message, $language)'
	 * ]
	 * @return self
	 * @throws Ssh2Exception
	 */
	public function connect(string $host = null, int $port = null, array $methods = null, array $callbacks = null): self
	{
		if(is_resource($this->ssh2Connection)) {
			$this->disconnect();
		}
		$this->host = $host ?? $this->host;
		$this->port = $port ?? $this->port;
		$this->info('Trying connection to host {host}:{port}', ['{host}' => $this->host, '{port}' => $this->port]);

		if(null === $callbacks) {
			$callbacks = ['disconnect' => 'self::disconnect_cb'];
		}

		$this->ssh2Connection = @ssh2_connect($this->host, $this->port, $methods, $callbacks);

		if (false === $this->ssh2Connection) {
			$this->critical("Connection refused to host {host}:{port}", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Connection refused to host $this->host:$this->port");
		}

		$this->info('Connection established success to host {host}:{port}', ['{host}' => $this->host, '{port}' => $this->port]);

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isConnected(): bool
	{
	    return is_resource($this->ssh2Connection);
	}

	/**
	 *
	 * @return false|string
	 */
	public function getErrors()
    {
        return fgets($this->errors, 8192);
    }

	/**
	 * Closes SSH socket.
	 * @return void
	 */
	public function disconnect(): void
	{
		if ($this->isConnected()) {
			$this->closeShell();

			if (@ssh2_disconnect($this->ssh2Connection)) {
				$this->ssh2Connection = false;
				$this->isAuthorised = false;
				$this->buffer = null;
				$this->info('Disconnect completed');
			} else {
				$this->critical('Disconnection fail');
			}
		}
	}

	/**
	 * Opens a shell over SSH for us to send commands and receive responses from.
	 *
	 * @param string $prompt
	 * @param string $termType The Terminal Type we will be using
	 * @param array $env Name/Value array of environment variables to set
	 * @param int $width Width of the terminal
	 * @param int $height Height of the terminal
	 * @param int $width_height_type
	 * @return self
	 * @throws Ssh2Exception
	 */
	public function openShell(string $prompt, string $termType = 'dumb', array $env = null, int $width = 240, int $height = 40, int $width_height_type = SSH2_TERM_UNIT_CHARS): self
	{
		if (is_resource($this->shell)) {
			throw new Ssh2Exception("Already opened shell at $this->host:$this->port connection");
		}
		if (false === $this->isConnected()) {
			$this->critical("Failed connecting to host {host}:{port}", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Failed connecting to host $this->host:$this->port");
		}
		if (false === $this->isAuthorised) {
			$this->critical("Failed authorisation on host {host}:{port}", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Failed authorisation on host $this->host:$this->port");
		}

		$this->info('Trying opening shell at {host}:{port} connection', ['{host}' => $this->host, '{port}' => $this->port]);

		$this->term_type = $termType;
		$this->env = is_array($env) && !empty($env) ? $env : null;
		$this->width = $width;
		$this->height = $height;
		$this->width_height_type = $width_height_type;

		$this->shell = @ssh2_shell(
			$this->ssh2Connection,
			$this->term_type,
			$this->env,
			$this->width,
			$this->height,
			$this->width_height_type
		);

		if (false === $this->shell) {
			$this->critical("Unable to establish shell at {host}:{port} connection", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Unable to establish shell at $this->host:$this->port connection");
		}

		$this->info('Shell opened success at {host}:{port} connection', ['{host}' => $this->host, '{port}' => $this->port]);

		$this->errors = @ssh2_fetch_stream($this->shell, SSH2_STREAM_STDERR);
//		$this->shell = $shell; //@ssh2_fetch_stream($shell, SSH2_STREAM_STDIO);
		if (false === @stream_set_blocking($this->shell, true)) {
            $this->critical("Unable to set blocking shell at {host}:{port} connection", ['{host}' => $this->host, '{port}' => $this->port]);
            throw new Ssh2Exception("Unable to set blocking shell at $this->host:$this->port connection");
        }
//		@stream_set_blocking($this->errors, true);

		$this->readTo($prompt);
		$this->clearBuffer();

		return $this;
	}

    /**
     * @return self
     */
	public function closeShell(): self
    {

        if(!fclose($this->shell)) {
            $this->critical('Shell stream closes is fail.');
        }
/*
        if(!fclose($this->errors)){
            $this->critical('Errors stream closes is fail.');
        }
*/
        $this->shell = false;
        $this->errors = false;
        return $this;
    }

	/**
	 * Clears internal command buffer.
	 *
	 * @return void
	 */
	private function clearBuffer()
	{
		$this->buffer = '';
	}

	/**
	 * Reads characters from the shell and adds them to command buffer.
	 * Handles telnet control characters. Stops when prompt is ecountered.
	 *
	 * @param string $prompt
	 * @return void
	 * @throws Ssh2Exception
	 *
	 */
	private function readTo(string $prompt): void
	{
		$this->prompt = str_replace('{username}', $this->username, $prompt);
		$this->info('Set prompt to "{prompt}"', ['{prompt}' => $this->prompt]);

		if (false === $this->isConnected()) {
			$this->critical("Failed connecting to host {host}:{port}", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Failed connecting to host $this->host:$this->port");
		}

        if (false === $this->shell) {
            $this->critical("Unable to establish shell at {host}:{port} connection", ['{host}' => $this->host, '{port}' => $this->port]);
            throw new Ssh2Exception("Unable to establish shell at $this->host:$this->port connection");
        }

		$this->clearBuffer();
        usleep($this->wait);
		$time = time() + $this->timeout;
		do {
			$c = fgetc($this->shell);

			if (false === $c) {
				$this->info("Couldn't find the requested : '" . $this->prompt . "', it was not in the data returned from server : '" . $this->buffer . "'");
				throw new Ssh2Exception("Couldn't find the requested : '" . $this->prompt . "', it was not in the data returned from server : '" . $this->buffer . "'");
			}

			// IANA TELNET OPTIONS
			if ($this->negotiateTelnetOptions($c)){
				continue;
			}

			if($this->encoding) {
				$c = mb_convert_encoding($c, $this->encoding);
			}

			$this->buffer .= $c;

			if ($this->logging) {
				@file_put_contents($this->logging, $c, FILE_APPEND);
			}

			if (preg_match("/{$this->prompt}\s?$/i", $this->buffer)) {
				if (is_float($this->executeTimestamp)) {
					$this->info("Command execution time is {timestamp} msec", ['{timestamp}' => microtime(true) - $this->executeTimestamp]);
				}
				$this->history .= $this->buffer;
				$this->debug($this->buffer);
				return;
			}

			if ($time < time()) {
				$this->history .= $this->buffer;
				$this->debug($this->buffer);
				$this->info("Timeout release before the prompt was read");
				return;
			}
		} while ($c !== $this->_NULL || $c !== $this->_DC1);

		$this->info("Data transmition is over");
	}

	/*
	* Get the full History of the shell session.
	*
	*/
	public function getHistory()
	{
		return $this->history;
	}

	/**
	 * Telnet control character magic.
	 *
	 * @param string $c
	 * @return bool
	 */
	private function negotiateTelnetOptions($c)
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
			default: return false;
		}
		$opt = fgetc($this->shell);
		$this->debug("Telnet option: " . $opt);
		return true;
	}

	/**
	 * Write command to a socket.
	 *
	 * @param string $cmd Stuff to write to socket
	 * @return void
	 * @throws Ssh2Exception
	 */
	public function write(string $cmd): void
	{
		if (false === $this->shell) {
			$this->critical("Unable to establish shell at {host}:{port} connection", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Unable to establish shell at $this->host:$this->port connection");
		}

		$this->clearBuffer();

		$this->info('Write command to host {host}:{port} => "{cmd}"', ['{host}' => $this->host, '{port}' => $this->port, '{cmd}' => $cmd]);
		$this->executeTimestamp = microtime(true);
		if ((!fwrite($this->shell, trim($cmd) . PHP_EOL)) < 0) {
			$this->critical("Error writing to shell at {host}:{port} connection", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Error writing to shell at $this->host:$this->port connection");
		}
	}

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
	public function send(string $cmd, string $prompt)
	{
		$this->write($cmd);
		$this->readTo($prompt);

		$buffer = $this->trimFirstLine(trim($this->buffer));
		$buffer = $this->trimPrompt($buffer, $this->prompt);

		return $buffer;
	}

	/**
	 * Trim the first line of multiline text
	 * @param string $text
	 * @return string
	 */
	public function trimFirstLine(string $text): string
	{
		return substr($text, strpos($text, PHP_EOL, 1) + 1 );
	}

	/**
	 * Trim the prompt line of multiline text
	 * @param string $text
	 * @param string $prompt
	 * @return string
	 */
	public function trimPrompt(string $text, string $prompt): string
	{
		return preg_replace("/$prompt\s*$/i", '', $text);
	}

	/**
	 * Return connection resource
	 * @return false|resource
	 */
	public function getConnection()
	{
		return $this->ssh2Connection;
	}

	/**
	 * Return shell if it was opened
	 * @return false|resource
	 */
	public function getShell()
	{
		return $this->shell;
	}

	/**
	 * Return list of negotiated methods
	 * @return array
	 */
	public function getMethodNegotiated(): array
	{
		return @ssh2_methods_negotiated($this->ssh2Connection);
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
	 */
	public function getFingerprint(int $flags = null): string
	{
		return @ssh2_fingerprint($this->ssh2Connection, $flags);
	}

	/**
	 * Returns the content of the command buffer.
	 *
	 * @return string Content of the command buffer
	 */
	public function getBuffer(): string
	{
		return $this->buffer;
	}

	/**
	 * Return an array of accepted authentication methods.
	 * Return true if the server does accept "none" as an authentication
     * Call this method before auth
	 * @param string $username
	 * @return array|bool
	 */
	public function getAuthMethods(string $username)
	{
		return @ssh2_auth_none($this->ssh2Connection, $username);
	}

	/**
	 * Authenticate as "none"
	 * @param string $username Remote user name.
	 * @return self
	 * @throws Ssh2Exception
	 */
	public function authNone(string $username): self
	{
		return $this->auth('none', $username);
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
		return $this->auth('password', $username, $password);
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
		return $this->auth('pubkey', $username, null, null, $pubkeyFile, $privkeyFile, $passphrase);
	}

	/**
	 * Authenticate using a public hostkey
	 * @param string $username
	 * @param string $hostname
	 * @param string $pubkeyFile
	 * @param string $privkeyFile
	 * @param string $passphrase If privkeyfile is encrypted (which it should be), the passphrase must be provided.
	 * @param string $local_username If local_username is omitted, then the value for username will be used for it.
	 * @return self
	 * @throws Ssh2Exception
	 */
	public function authHostbased(string $username, string $hostname, string $pubkeyFile, string $privkeyFile, string $passphrase = null, string $local_username = null): self
	{
		return $this->auth('hostbased', $username, null, $hostname, $pubkeyFile, $privkeyFile, $passphrase, $local_username);
	}

	/**
	 * Authenticate over SSH
	 * @param string $type none, password, pubkey or hostbased
	 * @param string $username
	 * @param string $password
	 * @param string $hostname
	 * @param string $pubkeyFile
	 * @param string $privkeyFile
	 * @param string $passphrase
	 * @param string $local_username
	 * @return self
	 * @throws Ssh2Exception
	 */
	public function auth(string $type = 'none', string $username = 'admin', string $password = null, string $hostname = null, string $pubkeyFile= null, string $privkeyFile = null, string $passphrase = null, string $local_username = null): self
	{
		if (!is_resource($this->ssh2Connection)) {
			$this->critical("Failed connecting to host {host}:{port}", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Failed connecting to host $this->host:$this->port");
		}
		$this->username = $username;
		switch ($type) {
			case 'password': {
				$this->isAuthorised = @ssh2_auth_password($this->ssh2Connection, $username, $password);
				break;
			}
			case 'pubkey': {
				$this->isAuthorised = @ssh2_auth_pubkey_file($this->ssh2Connection, $username, $pubkeyFile, $privkeyFile, $passphrase);
				break;
			}
			case 'hostbased': {
				$this->isAuthorised = @ssh2_auth_hostbased_file($this->ssh2Connection, $username, $hostname, $pubkeyFile, $privkeyFile, $passphrase, $local_username);
				break;
			}
			case 'none': {}
			default: {
				$this->isAuthorised = @ssh2_auth_none($this->ssh2Connection, $username);
			}
		}

		if (false === $this->isAuthorised) {
			$this->critical("Failed authentication on host {host}:{port}", ['{host}' => $this->host, '{port}' => $this->port]);
			throw new Ssh2Exception("Failed authentication on host $this->host:$this->port");
		}
		$this->info("Password authentication success");
		return $this;
	}

	/**
	 * @param int $timeout in seconds.
     * @return void
	 */
	public function setTimeout(int $timeout): void
	{
		$this->timeout = $timeout;
		$this->info("Timeout was set to {timeout} seconds", ['{timeout}' => $timeout]);
	}

	/**
	 * @param string $level
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log(string $level, string $message, array $context = array()): void
	{
		if (!empty($context)) {
			$message = str_replace(array_keys($context), array_values($context), $message);
		}
		$text = '[' . date('D M d H:i:s Y', time()) . '] ' . $this->host. ' ' . $level . ': ' . $message . PHP_EOL;
		if ($this->logging) {
			file_put_contents($this->logging, $text, FILE_APPEND);
		}
		if ($this->screenLogging) {
			print $text;
		}
	}

    /**
     * @param $message
     * @throws Ssh2Exception
     */
	public static function ignore_cb(string $message): void
	{
		var_dump($message);
		throw new Ssh2Exception($message);
	}

    /**
     * @param $packet
     * @return bool
     * @throws Ssh2Exception
     */
	public static function macerror_cb($packet): bool
	{
		var_dump($packet);
        throw new Ssh2Exception("MAC error");
	}

    /**
     * @param $message
     * @param $language
     * @param $always_display
     * @throws Ssh2Exception
     */
	public static function debug_cb($message, $language, $always_display): void
	{
		var_dump(func_get_args());
		$msg = sprintf("Debug msg: %s\nLanguage: %s\nDisplay: %s\n", $message, $language, $always_display);
		throw new Ssh2Exception($msg);
	}

	/**
	 * Notify the user if the connection terminates.
	 *
	 * @param string $reason
	 * @param string $message
	 * @param string $language
	 *
	 * @return void
	 * @throws Ssh2Exception
	 */
	public static function disconnect_cb($reason, $message, $language): void
	{
		$phpSsh2 = static::class;
		var_dump($phpSsh2);
		$msg = sprintf("SSH disconnected with reason code [%d] and message: %s\n", $reason, $message);

		throw new Ssh2Exception($msg);
	}

	protected function exception($type, $msg, $context)
    {
        if ($type === 'critical') {
            $this->critical($msg, $context);
            $this->disconnect();
            throw new Ssh2Exception("Failed authentication on host $this->host:$this->port");
        }
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
