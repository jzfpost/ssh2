<?php

namespace jzfpost\ssh2\Conf\Methods;

use jzfpost\ssh2\Conf\TypeEnumInterface;

enum HostKeyEnum: string implements TypeEnumInterface
{
    case RSA = 'ssh-rsa';
    case DSS = 'ssh-dss';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFromValue(string $value): self
    {
        return self::from($value);
    }
}
