<?php declare(strict_types=1);

namespace jzfpost\ssh2\Conf;

enum FPAlgorithmEnum implements IntEnumInterface
{
    case md5;
    case sha1;
    case hex;
    case raw;

    public function getValue(): int
    {
        return match ($this) {
            self::md5 => SSH2_FINGERPRINT_MD5,
            self::sha1 => SSH2_FINGERPRINT_SHA1,
            self::hex => SSH2_FINGERPRINT_HEX,
            self::raw => SSH2_FINGERPRINT_RAW
        };
    }

    public function getFromValue(int $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->getValue() === $value) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Not implements " . self::class . " with case: $value");
    }
}
