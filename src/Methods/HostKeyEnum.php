<?php

declare(strict_types=1);

namespace jzfpost\ssh2\Methods;

enum HostKeyEnum: string implements MethodsEnumInterface
{
    case RSA = 'ssh-rsa';
    case DSS = 'ssh-dss';
}
