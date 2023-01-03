<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\CryptMethods;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

interface CryptMethodsInterface
{
    #[pure]
    #[ArrayShape([
        'kex' => "string",
        'hostkey' => "string",
        'client_to_server' => [
            'crypt' => "string",
            'comp' => "string",
            'mac' => "string",
            'lang' => "string"
        ],
        'server_to_client' => [
            'crypt' => "string",
            'comp' => "string",
            'mac' => "string",
            'lang' => "string"
        ]
    ])]
    public function asArray(): array;
}