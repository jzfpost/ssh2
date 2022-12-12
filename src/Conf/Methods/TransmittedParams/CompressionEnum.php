<?php

namespace jzfpost\ssh2\Conf\Methods\TransmittedParams;

use jzfpost\ssh2\Conf\TypeEnumInterface;

enum CompressionEnum: string implements TypeEnumInterface
{
    case none = 'none';
    case zlib = 'zlib';
    case zlibOpenSsh = 'zlib@openssh.com';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFromValue(string $value): self
    {
        return self::from($value);
    }
}
