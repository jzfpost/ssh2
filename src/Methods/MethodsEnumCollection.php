<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Methods;

use jzfpost\ssh2\Methods\TransmittedParams\CompressionEnum;
use jzfpost\ssh2\Methods\TransmittedParams\CryptEnum;
use jzfpost\ssh2\Methods\TransmittedParams\HmacEnum;

abstract class MethodsEnumCollection implements MethodsEnumCollectionInterface, \Stringable
{
    /**
     * @var KexEnum[]|HostKeyEnum[]|CryptEnum[]|HmacEnum[]|CompressionEnum[]
     */
    protected array $enums = [];

    abstract public function add(MethodsEnumInterface $enum): self;

    public function clear(): self
    {
        $new = clone $this;
        $new->enums = [];

        return $new;
    }

    public function __toString(): string
    {
        $values = [];
        foreach ($this->enums as $enum) {
            $values[] = $enum->value;
        }

        return implode(', ', array_unique($values));
    }
}