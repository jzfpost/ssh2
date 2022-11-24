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

enum WidthHeightTypeEnum implements TypeInterface
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
            WidthHeightTypeEnum::chars => SSH2_TERM_UNIT_CHARS,
            WidthHeightTypeEnum::pixels => SSH2_TERM_UNIT_PIXELS,
        };
    }
}