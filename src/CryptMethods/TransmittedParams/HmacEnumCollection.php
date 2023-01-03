<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\CryptMethods\TransmittedParams;

use jzfpost\ssh2\CryptMethods\MethodsEnumCollection;
use jzfpost\ssh2\CryptMethods\MethodsEnumInterface;

final class HmacEnumCollection extends MethodsEnumCollection
{
    public function __construct(HmacEnum $enum)
    {
        $this->enums[] = $enum;
    }

    public function add(MethodsEnumInterface $enum): self
    {
        if ($enum instanceof HmacEnum) {
            $this->enums[] = $enum;
        }

        return $this;
    }
}