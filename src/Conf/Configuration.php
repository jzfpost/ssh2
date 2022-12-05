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

/**
 * USAGE:
 * ```php
 * $conf = new Configuration('www.site.com')->setTermType(TermTypeEnum::xterm);
 * $ssh = new SSH($conf);
 * ```
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-var positive-int the response timeout in seconds (s)
     */
    private int $timeout = 10;
    /**
     * @psalm-var positive-int Delay execution in microseconds (ms)
     */
    private int $wait = 3500;
    /**
     * Methods may be an associative array with any of the ssh2 connect parameters
     * @psalm-param array<array-key, string|array<array-key, string>> $methods
     * $methods = [
     *     'kex' => 'diffie-hellman-group1-sha1, diffie-hellman-group14-sha1, diffie-hellman-group-exchange-sha1',
     *     'hostkey' => 'ssh-rsa, ssh-dss',
     *     'client_to_server' => [
     *         'crypt' => 'rijndael-cbc@lysator.liu.se, aes256-cbc, aes192-cbc, aes128-cbc, 3des-cbc, blowfish-cbc, cast128-cbc, arcfour',
     *         'comp' => 'zlib|none',
     *         'mac' => 'hmac-sha1, hmac-sha1-96, hmac-ripemd160, hmac-ripemd160@openssh.com'
     *      ],
     *     'server_to_client' => [
     *         'crypt' => 'rijndael-cbc@lysator.liu.se, aes256-cbc, aes192-cbc, aes128-cbc, 3des-cbc, blowfish-cbc, cast128-cbc, arcfour',
     *         'comp' => 'zlib|none',
     *         'mac' => 'hmac-sha1, hmac-sha1-96, hmac-ripemd160, hmac-ripemd160@openssh.com'
     *     ]
     * ]
     */
    private ?array $methods = null;
    /**
     * Maybe an associative array with any of the ssh2 connect parameters
     * @psalm-param array<array-key, callable> $callbacks
     * $callbacks = [
     *     'ignore' => 'self::ignore_cb($message)',
     *     'debug' => 'self::debug_cb($message, $language, $always_display)',
     *     'macerror' => 'self::macerror_cb($packet)', //function must return bool
     *     'disconnect' => 'self::disconnect_cb($reason, $message, $language)'
     * ]
     */
    private ?array $callbacks = [
        'ignore' => 'jzfpost\\ssh2\\Conf\\Callbacks::ignore_cb',
        'macerror' => 'jzfpost\\ssh2\\Conf\\Callbacks::macerror_cb',
        'disconnect' => 'jzfpost\\ssh2\\Conf\\Callbacks::disconnect_cb',
        'debug' => 'jzfpost\\ssh2\\Conf\\Callbacks::debug_cb'
    ];
    private TermTypeEnum $termType = TermTypeEnum::vanilla;
    private ?array $env = null;
    /**
     * @psalm-var positive-int
     */
    private int $width = SSH2_DEFAULT_TERM_WIDTH;
    /**
     * @psalm-var positive-int
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

    public function get(string $property): mixed
    {
        return $this->$property;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function set(string $property, mixed $value): self
    {
        $new = clone $this;
        $new->$property = $value;

        return $new;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getDefaultProperties(): array
    {
        $array = [];
        $properties = get_class_vars(self::class);

        foreach ($properties as $property => $value) {
            $array[$property] = $value instanceof TypeEnumInterface ? $value->getValue() : $value;
        }

        return $array;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getAsArray(): array
    {
        $array = [];
        $properties = get_class_vars(self::class);
        $keys = array_keys($properties);

        foreach ($keys as $property) {
            $value = $this->$property;
            $array[$property] = $value instanceof TypeEnumInterface ? $value->getValue() : $value;
        }

        return $array;
    }

    /**
     * @psalm-param array<string, bool|positive-int|non-empty-string|string|array> $options
     * @psalm-suppress MixedMethodCall
     */
    public function setFromArray(array $options = []): self
    {
        $new = clone $this;

        foreach ($options as $property => $value) {

            $new->$property = $this->$property instanceof TypeEnumInterface
                ? $this->$property->getFromValue($value)
                : $value;
        }

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
        return $this->set('timeout', $timeout);
    }

    public function getEnv(): ?array
    {
        return $this->env;
    }

    /**
     * @param array<string, string>|null $env
     * @return $this
     */
    public function setEnv(?array $env = null): self
    {
        return $this->set('env', $env);
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
        return $this->set('wait', $wait);
    }

    public function getMethods(): ?array
    {
        return $this->methods;
    }

    public function setMethods(?array $methods): self
    {
        return $this->set('methods', $methods);
    }

    public function getCallbacks(): ?array
    {
        return $this->callbacks;
    }

    public function setCallbacks(?array $callbacks): self
    {
        return $this->set('callbacks', $callbacks);
    }

    public function getTermType(): string
    {
        return $this->termType->getValue();
    }

    public function setTermType(TermTypeEnum $termType): self
    {
        return $this->set('termType', $termType);
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
        return $this->set('width', $width);
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
        return $this->set('height', $height);
    }

    public function getWidthHeightType(): int
    {
        return $this->widthHeightType->getValue();
    }

    public function setWidthHeightType(WidthHeightTypeEnum $widthHeightType): self
    {
        return $this->set('widthHeightType', $widthHeightType);
    }

    public function getPty(): ?string
    {
        return $this->pty;
    }

    public function setPty(?string $pty = null): self
    {
        return $this->set('pty', $pty);
    }
}