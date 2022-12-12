<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods;

use JetBrains\PhpStorm\ArrayShape;

interface MethodsInterface
{
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
    public function getAsArray(): array;
}