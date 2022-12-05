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

enum WidthHeightTypeEnum implements TypeEnumInterface
{
    /**
     * SSH2_TERM_UNIT_CHARS
     */
    case chars;
    /**
     * SSH2_TERM_UNIT_PIXELS
     */
    case pixels;

    public function getValue(): int
    {
        return match ($this) {
            self::chars => SSH2_TERM_UNIT_CHARS,
            self::pixels => SSH2_TERM_UNIT_PIXELS,
        };
    }

    public function getFromValue(int|string $value): WidthHeightTypeEnum
    {
        foreach (self::cases() as $case) {
            if ($case->getValue() === $value || $case->name === $value) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Not implements " . self::class . " with case: $value");
    }
}