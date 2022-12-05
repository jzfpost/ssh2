<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Conf;

use PHPUnit\Framework\TestCase;

final class WidthHeightTypeEnumTest extends TestCase
{

    public function testTypeInterface()
    {
        $this->assertInstanceOf(TypeEnumInterface::class, WidthHeightTypeEnum::chars);
    }

    public function testGetValue()
    {
        $this->assertEquals(SSH2_TERM_UNIT_CHARS, WidthHeightTypeEnum::chars->getValue());
        $this->assertEquals(SSH2_TERM_UNIT_PIXELS, WidthHeightTypeEnum::pixels->getValue());
    }
}
