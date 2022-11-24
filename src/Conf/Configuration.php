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

namespace jzfpost\ssh2\Conf;

use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionException;

/**
 * USAGE:
 * ```php
 * $conf = new Configuration('www.site.com')->setTermType(TermTypeEnum::xterm);
 * $ssh = new SSH($conf);
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
     * Maybe an associative array with any of the ssh2 connect parameters
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
    private TermTypeEnum $termType = TermTypeEnum::vanilla;
    /**
     * @var array<string, string>|null
     */
    private ?array $env = null;
    /**
     * @var positive-int
     */
    private int $width = SSH2_DEFAULT_TERM_WIDTH;
    /**
     * @var positive-int
     */
    private int $height = SSH2_DEFAULT_TERM_HEIGHT;
    /**
     * width_height_type should be one of
     * WidthHeightTypeEnum::chars
     * or
     * WidthHeightTypeEnum::pixels.
     */
    private WidthHeightTypeEnum $widthHeightType = WidthHeightTypeEnum::chars;
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

    #[Pure] public function getDefaultProperties(): array
    {
        $new = new self();
        return [
            'host' => $new->getHost(),
            'port' => $new->getPort(),
            'timeout' => $new->getTimeout(),
            'wait' => $new->getWait(),
            'encoding' => $new->getEncoding(),
            'methods' => $new->getMethods(),
            'callbacks' => $new->getCallbacks(),
            'termType' => $new->getTermType(),
            'env' => $new->getEnv(),
            'width' => $new->getWidth(),
            'height' => $new->getHeight(),
            'widthHeightType' => $new->getWidthHeightType(),
            'pty' => $new->getPty()
        ];
    }

    /**
     * @return array
     */
    #[Pure] public function getAsArray(): array
    {
        return [
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'timeout' => $this->getTimeout(),
            'wait' => $this->getWait(),
            'encoding' => $this->getEncoding(),
            'methods' => $this->getMethods(),
            'callbacks' => $this->getCallbacks(),
            'termType' => $this->getTermType(),
            'env' => $this->getEnv(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'widthHeightType' => $this->getWidthHeightType(),
            'pty' => $this->getPty()
        ];
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

    /**
     * @return array<string, string>|null
     */
    public function getEnv(): ?array
    {
        return $this->env;
    }

    /**
     * @param array<string, string>|null $env
     * @return $this
     */
    public function setEnv(array $env = null): self
    {
        $new = clone $this;
        $new->env = $env;

        return $new;
    }

    /**
     * @return positive-int
     */
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

    public function getTermType(): TermTypeEnum
    {
        return $this->termType;
    }

    public function setTermType(TermTypeEnum $termType): self
    {
        $new = clone $this;
        $new->termType = $termType;

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

    public function getWidthHeightType(): WidthHeightTypeEnum
    {
        return $this->widthHeightType;
    }

    public function setWidthHeightType(WidthHeightTypeEnum $widthHeightType): self
    {
        $new = clone $this;
        $new->widthHeightType = $widthHeightType;

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