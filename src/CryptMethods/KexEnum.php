<?php

declare(strict_types=1);

namespace jzfpost\ssh2\CryptMethods;

enum KexEnum: string implements MethodsEnumInterface
{
    case dhGroup1Sha1 = 'diffie-hellman-group1-sha1';
    case dhGroup14Sha1 = 'diffie-hellman-group14-sha1';
    case dhGroupExchangeSha1 = 'diffie-hellman-group-exchange-sha1';
    case dhGroupExchangeSha256 = 'diffie-hellman-group-exchange-sha256';
    case curve25519Sha256 = 'curve25519-sha256';
}
