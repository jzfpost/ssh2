<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods;

use JetBrains\PhpStorm\ArrayShape;
use jzfpost\ssh2\Conf\Methods\TransmittedParams\TransmittedParams;

/**
 * @psalm-immutable
 */
final class Methods implements MethodsInterface
{
    public function __construct(
        private readonly KexEnumCollection $kex = new KexEnumCollection(KexEnum::dhGroup1Sha1),
        private readonly HostKeyEnumCollection $hostKey = new HostKeyEnumCollection(HostKeyEnum::RSA),
        private readonly TransmittedParams $clientToServer = new TransmittedParams(),
        private readonly TransmittedParams $serverToClient = new TransmittedParams()
    )
    {

    }

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
    public function getAsArray(): array
    {
        return [
            'kex' => (string) $this->kex,
            'hostkey' => (string) $this->hostKey,
            'client_to_server' => $this->clientToServer->getAsArray(),
            'server_to_client' => $this->serverToClient->getAsArray()
        ];
    }
}