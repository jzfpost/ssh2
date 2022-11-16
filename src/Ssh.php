<?php declare(strict_types=1);
/**
 * SSH2 helper class.
 *
 * PHP version ^8.1
 *
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://giathub/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 * @see         "php -i | grep ssh2". Package tested with php-ssh ext-ssh2 version => 1.3.1 on libssh2 version => 1.8.0
 * @version     0.4.0
 *
 */

namespace jzfpost\ssh2;

use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exceptions\SshException;
use Psr\Log\LoggerTrait;

use function function_exists;
use function register_shutdown_function;

/**
 *
 * USAGE:
 * ```php
 * $conf = (new Configuration('192.168.1.1'))
 *  ->setTermType('xterm')
 *  ->setLoggingFileName("/var/log/ssh2/log.txt")
 *  ->setDebugMode();
 *
 * $ssh2 = new Ssh($conf);
 * $shell = $ssh2->connect()
 *  ->authPassword($username, $password)
 *  ->getShell()
 *  ->open(Shell::PROMPT_LINUX);
 *
 * $result = $shell->send('ls -a', Shell::PROMPT_LINUX);
 * $ssh2->disconnect();
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
final class Ssh extends AbstractSshObject implements SshInterface
{
    use LoggerTrait;

    public function __construct(Configuration $configuration = new Configuration())
    {
        $this->configuration = $configuration;

        if (!function_exists('ssh2_connect')) {
            throw new SshException("ssh2_connect function doesn't exist! Please install \"ext-ssh2\" php module.");
        }

        $this->info($configuration->isDebugMode() ? "DEBUG mode is ON" : "DEBUG mode is OFF");
        $this->info($this->loggingFileName ? "LOGGING start to file '{value}'" : 'LOGGING set to OFF', ['{value}' => $this->loggingFileName]);
        $this->info("{property} set to {value} seconds", ['{property}' => 'TIMEOUT', '{value}' => (string)$this->timeout]);
        $this->info("{property} set to {value} microseconds", ['{property}' => 'WAIT', '{value}' => (string)$this->wait]);
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
            throw new SshException("Connection already exists on $this");
        }

        $this->info('Trying connection to host {host}:{port}');

        $this->session = @ssh2_connect($this->host, $this->port, $this->methods, $this->callbacks);

        if (!$this->isConnected()) {
            $this->critical("Connection refused to host {host}:{port}");
            throw new SshException("Connection refused to host $this");
        }

        $this->info('Connection established success to host {host}:{port}');

        return $this;
    }

}
