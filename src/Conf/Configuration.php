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

namespace jzfpost\ssh2\Conf;

use ReflectionClass;
use ReflectionException;

/**
 * USAGE:
 * ```php
 * $conf = new Configuration()->setHost('192.168.1.1')->setDebugMode()->setEncoding("UTF8");
 * ```
 */
final class Configuration
{

    /**
     * @var non-empty-string hostname or IP address
     */
    private string $host;
    /**
     * @var positive-int
     */
    private int $port;
    /**
     * @var positive-int the response timeout in seconds (s)
     */
    private int $timeout = 10;
    /**
     * @var positive-int Delay execution in microseconds (ms)
     */
    private int $wait = 3500;
    /**
     * @var non-empty-string|false Encoding characters
     */
    private string|false $encoding = false;
    /**
     * @var non-empty-string|false file path for logging
     */
    private string|false $loggingFileName = false;
    private bool $debugMode = false;
    private string $dateFormat = 'Y M d H:i:s';
    /**
     * Methods may be an associative array with any of the ssh2 connect parameters
     * @param array<array-key, string|array<array-key, string>> $methods
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
     */
    private array $methods = [];
    /**
     * May be an associative array with any of the ssh2 connect parameters
     * @param array<array-key, callable> $callbacks
     * $callbacks = [
     *     'ignore' => 'self::ignore_cb($message)',
     *     'debug' => 'self::debug_cb($message, $language, $always_display)',
     *     'macerror' => 'self::macerror_cb($packet)', //function must return bool
     *     'disconnect' => 'self::disconnect_cb($reason, $message, $language)'
     * ]
     */
    private array $callbacks = [
        'ignore' => 'jzfpost\\Conf\\Callbacks::ignore_cb',
        'macerror' => 'jzfpost\\Conf\\Callbacks::macerror_cb',
        'disconnect' => 'jzfpost\\Conf\\Callbacks::disconnect_cb',
        'debug' => 'jzfpost\\Conf\\Callbacks::debug_cb'
    ];

    private ?string $termType = 'dumb';
    private array $env = [null];
    /**
     * @var positive-int
     */
    private int $width = 240;
    /**
     * @var positive-int
     */
    private int $height = 240;
    /**
     * width_height_type should be one of
     * SSH2_TERM_UNIT_CHARS or
     * SSH2_TERM_UNIT_PIXELS.
     * @var int
     */
    private int $width_height_type = SSH2_TERM_UNIT_CHARS;
    private ?string $pty = null;

    /**
     * @psalm-param non-empty-string $host
     * @psalm-param positive-int $port
     */
    public function __construct(string $host = 'localhost', int $port = 22)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function getDefaultProperties(): array
    {
        return (new ReflectionClass(clone $this))->getDefaultProperties();
    }

    /**
     * @throws ReflectionException
     */
    public function getAsArray(): array
    {
        /** @var array<string, mixed> $array */
        $array = [];

        $new = new ReflectionClass($this);
        foreach ($new->getProperties() as $item) {
            $name = $item->getName();
            $property = $new->getProperty($name);
            $array[$name] = $property->getValue($this);
        }

        return $array;
    }

    /**
     * @param array<string, bool|positive-int|non-empty-string|string> $options
     * @return $this
     */
    public function setFromArray(array $options = []): self
    {
        $new = clone $this;
        foreach ($options as $property => $value) {
            if (property_exists($new, $property)) {
                $new->$property = $value;
            }
        }

        return $new;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @psalm-param non-empty-string $host
     */
    public function setHost(string $host): self
    {
        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @psalm-param positive-int $port
     */
    public function setPort(int $port): self
    {
        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @psalm-param positive-int $timeout
     */
    public function setTimeout(int $timeout): self
    {
        $new = clone $this;
        $new->timeout = $timeout;

        return $new;
    }

    public function getEnv(): array
    {
        return $this->env;
    }


    public function setEnv(array $env = [null]): self
    {
        $new = clone $this;
        $new->env = $env;

        return $new;
    }

    public function getWait(): int
    {
        return $this->wait;
    }

    /**
     * @psalm-param positive-int $wait
     */
    public function setWait(int $wait): self
    {
        $new = clone $this;
        $new->wait = $wait;

        return $new;
    }

    public function getEncoding(): bool|string
    {
        return $this->encoding;
    }

    /**
     * @psalm-param non-empty-string|false $encoding
     */
    public function setEncoding(string|false $encoding): self
    {
        $new = clone $this;
        $new->encoding = $encoding;

        return $new;
    }

    public function getLoggingFileName(): bool|string
    {
        return $this->loggingFileName;
    }

    /**
     * @psalm-param non-empty-string|false $loggingFileName
     */
    public function setLoggingFileName(string|false $loggingFileName): self
    {
        $new = clone $this;
        $new->loggingFileName = $loggingFileName;

        return $new;
    }

    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * @param bool $debugMode
     * @return $this
     */
    public function setDebugMode(bool $debugMode = true): self
    {
        $new = clone $this;
        $new->debugMode = $debugMode;

        return $new;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(string $dateFormat): self
    {
        $new = clone $this;
        $new->dateFormat = $dateFormat;

        return $new;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function setMethods(array $methods): self
    {
        $new = clone $this;
        $new->methods = $methods;

        return $new;
    }

    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    public function setCallbacks(array $callbacks): self
    {
        $new = clone $this;
        $new->callbacks = $callbacks;

        return $new;
    }

    public function getTermType(): ?string
    {
        return $this->termType;
    }

    public function setTermType(string $termType): self
    {
        $new = clone $this;
        $new->termType = empty($termType) ? null : $termType;

        return $new;
    }

    /**
     * @return positive-int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param positive-int $width
     */
    public function setWidth(int $width): self
    {
        $new = clone $this;
        $new->width = $width;

        return $new;
    }

    /**
     * @return positive-int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param positive-int $height
     */
    public function setHeight(int $height): self
    {
        $new = clone $this;
        $new->height = $height;

        return $new;
    }

    public function getWidthHeightType(): int
    {
        return $this->width_height_type;
    }

    public function setWidthHeightType(int $width_height_type): self
    {
        $new = clone $this;
        $new->width_height_type = $width_height_type;

        return $new;
    }

    public function getPty(): ?string
    {
        return $this->pty;
    }

    public function setPty(?string $pty = null): self
    {
        $new = clone $this;
        $new->pty = $pty;

        return $new;
    }


}