<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Methods\TransmittedParams;

use jzfpost\ssh2\Methods\MethodsEnumCollection;
use jzfpost\ssh2\Methods\MethodsEnumInterface;

final class CryptEnumCollection extends MethodsEnumCollection
{
    public function __construct(CryptEnum $enum)
    {
        $this->enums[] = $enum;
    }

    public function add(MethodsEnumInterface $enum): self
    {
        if ($enum instanceof CryptEnum) {
            $this->enums[] = $enum;
        }

        return $this;
    }
}