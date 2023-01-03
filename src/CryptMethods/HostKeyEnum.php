<?php

declare(strict_types=1);

namespace jzfpost\ssh2\CryptMethods;

enum HostKeyEnum: string implements MethodsEnumInterface
{
    case RSA = 'ssh-rsa';
    case DSS = 'ssh-dss';
    case ecdsaSha2Nistp256 = 'ecdsa-sha2-nistp256';
}
