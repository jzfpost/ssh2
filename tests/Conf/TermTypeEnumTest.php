<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Conf;

use PHPUnit\Framework\TestCase;

final class TermTypeEnumTest extends TestCase
{
    public function testTypeInterface()
    {
        $this->assertInstanceOf(TypeInterface::class, TermTypeEnum::xterm);
    }

    public function testGetValue()
    {
        $this->assertEquals('xterm', TermTypeEnum::xterm->getValue());
        $this->assertEquals('dumb', TermTypeEnum::dumb->getValue());
    }
}
