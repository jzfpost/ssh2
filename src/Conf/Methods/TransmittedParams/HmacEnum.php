<?php

namespace jzfpost\ssh2\Conf\Methods\TransmittedParams;

use jzfpost\ssh2\Conf\TypeEnumInterface;

enum HmacEnum: string implements TypeEnumInterface
{
    case md5 = 'hmac-md5';
    case md5v96 = 'hmac-md5-96';
    case sha1 = 'hmac-sha1';
    case sha1v96 = 'hmac-sha1-96';
    case sha2v256 = 'hmac-sha2-256';
    case sha2v512 = 'hmac-sha2-512';
    case ripemd160 = 'hmac-ripemd160';
    case none = 'none';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFromValue(string $value): self
    {
        return self::from($value);
    }
}
