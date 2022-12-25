<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Methods;

final class KexEnumCollection extends MethodsEnumCollection
{
    public function __construct(KexEnum $enum)
    {
        $this->enums[] = $enum;
    }

    public function add(MethodsEnumInterface $enum): self
    {
        if ($enum instanceof KexEnum) {
            $this->enums[] = $enum;
        }

        return $this;
    }
}