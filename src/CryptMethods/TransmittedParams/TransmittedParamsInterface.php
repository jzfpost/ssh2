<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\CryptMethods\TransmittedParams;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

interface TransmittedParamsInterface
{
    #[Pure]
    #[ArrayShape([
        'crypt' => "string",
        'comp' => "string",
        'mac' => "string",
        'lang' => "string"
    ])]
    public function asArray(): array;

}