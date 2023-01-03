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

namespace jzfpost\ssh2\Conf;

use jzfpost\ssh2\CryptMethods\CryptMethodsInterface;

/**
 * USAGE:
 * ```php
 * $conf = new Configuration(new FileLogger("/var/log/ssh2/log.txt", true))->setTermType(TermTypeEnum::xterm);
 * $ssh = new Ssh($conf, $logger);
 * ```
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @psalm-var positive-int the response timeout in seconds (s)
     */
    private int $timeout = 10;
    /**
     * @psalm-var positive-int Delay execution in microseconds (ms)
     */
    private int $wait = 3500;
    private CryptMethodsInterface|null $cryptMethods = null;
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
    private array|null $callbacks = [
        'ignore' => 'jzfpost\\ssh2\\Conf\\Callbacks::ignore_cb',
        'macerror' => 'jzfpost\\ssh2\\Conf\\Callbacks::macerror_cb',
        'disconnect' => 'jzfpost\\ssh2\\Conf\\Callbacks::disconnect_cb',
        'debug' => 'jzfpost\\ssh2\\Conf\\Callbacks::debug_cb'
    ];
    private TermTypeEnum $termType = TermTypeEnum::vanilla;
    private array|null $env = null;
    /**
     * @psalm-var positive-int
     */
    private int $width = SSH2_DEFAULT_TERM_WIDTH;
    /**
     * @psalm-var positive-int
     */
    private int $height = SSH2_DEFAULT_TERM_HEIGHT;
    private WidthHeightTypeEnum $widthHeightType = WidthHeightTypeEnum::chars;
    private FPAlgorithmEnum $fingerPrintAlgorithm = FPAlgorithmEnum::md5;

    /**
     * @psalm-suppress MixedAssignment
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $property => $value) {
            $this->$property = $value;
        }
    }

    public function get(string $property): mixed
    {
        return $this->$property;
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @psalm-param mixed $value
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
        return get_class_vars(self::class);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getAsArray(): array
    {
        $array = [];
        $properties = $this->getDefaultProperties();
        $keys = array_keys($properties);

        foreach ($keys as $property) {
            $array[$property] = $this->$property;
        }

        return $array;
    }

    /**
     * @inheritDoc
     */
    public function setFromArray(array $options = []): self
    {
        return new self($options);
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
        return $this->cryptMethods?->asArray();
    }

    public function getMethodsObject(): ?CryptMethodsInterface
    {
        return $this->cryptMethods;
    }

    public function setMethods(?CryptMethodsInterface $methods = null): self
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

    public function getTermTypeEnum(): TermTypeEnum
    {
        return $this->termType;
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

    public function getWidthHeightTypeEnum(): WidthHeightTypeEnum
    {
        return $this->widthHeightType;
    }

    public function setWidthHeightType(WidthHeightTypeEnum $widthHeightType): self
    {
        return $this->set('widthHeightType', $widthHeightType);
    }

    public function getFingerPrintAlgorithm(): int
    {
        return $this->fingerPrintAlgorithm->getValue();
    }

    public function getFingerPrintAlgorithmEnum(): FPAlgorithmEnum
    {
        return $this->fingerPrintAlgorithm;
    }

    public function setFingerPrintAlgorithm(FPAlgorithmEnum $fingerPrintAlgorithm): self
    {
        return $this->set('fingerPrintAlgorithm', $fingerPrintAlgorithm);
    }

}