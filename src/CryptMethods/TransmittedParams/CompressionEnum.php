<?php

declare(strict_types=1);

namespace jzfpost\ssh2\CryptMethods\TransmittedParams;

use jzfpost\ssh2\CryptMethods\MethodsEnumInterface;

enum CompressionEnum: string implements MethodsEnumInterface
{
    case none = 'none';
    case zlib = 'zlib';
    case zlibOpenSsh = 'zlib@openssh.com';
}
