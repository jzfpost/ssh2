<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\CryptMethods;

interface MethodsEnumCollectionInterface
{
    public function add(MethodsEnumInterface $enum): self;

    public function clear(): self;
}