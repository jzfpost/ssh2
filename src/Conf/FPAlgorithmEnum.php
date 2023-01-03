<?php

declare(strict_types=1);
/**
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://github/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 */

namespace jzfpost\ssh2\Conf;

enum FPAlgorithmEnum implements IntEnumInterface
{
    case md5;
    case sha1;
    case raw;

    final public function getValue(): int
    {
        return match ($this) {
            self::md5 => SSH2_FINGERPRINT_MD5,
            self::sha1 => SSH2_FINGERPRINT_SHA1,
            self::raw => SSH2_FINGERPRINT_RAW
        };
    }

    final public function getFromValue(int $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->getValue() === $value) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Not implements " . self::class . " with case: $value");
    }
}
