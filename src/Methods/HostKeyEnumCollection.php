<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Methods;

final class HostKeyEnumCollection extends MethodsEnumCollection
{
    public function __construct(HostKeyEnum $enum)
    {
        $this->enums[] = $enum;
    }

    public function add(MethodsEnumInterface $enum): self
    {
        if ($enum instanceof HostKeyEnum) {
            $this->enums[] = $enum;
        }

        return $this;
    }
}