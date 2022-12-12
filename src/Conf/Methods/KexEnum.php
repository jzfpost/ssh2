<?php

namespace jzfpost\ssh2\Conf\Methods;

use jzfpost\ssh2\Conf\TypeEnumInterface;

enum KexEnum: string implements TypeEnumInterface
{
    case dhGroup1Sha1 = 'diffie-hellman-group1-sha1';
    case dhGroup14Sha1 = 'diffie-hellman-group14-sha1';
    case dhGroupExchangeSha1 = 'diffie-hellman-group-exchange-sha1';
    case dhGroupExchangeSha256 = 'diffie-hellman-group-exchange-sha256';

    public function getValue(): string
    {
        return $this->value;
    }

    public function getFromValue(string $value): self
    {
        return self::from($value);
    }
}
