<?php declare(strict_types=1);
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

enum TermTypeEnum implements TypeEnumInterface
{
    case ansi;
    case dumb;
    case hurd;
    case pcansi;
    case linux;
    case xterm;
    case xterm_r6;
    case vt100;
    case vt102;
    case vanilla;

    public function getValue(): string
    {
        return $this->name;
    }

    public function getFromValue(int|string $value): TermTypeEnum
    {
        foreach (self::cases() as $case) {
            if ($case->getValue() === $value) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Not implements " . self::class . " with case: $value");
    }
}