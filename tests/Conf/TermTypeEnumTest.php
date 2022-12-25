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
        $this->assertInstanceOf(TypeEnumInterface::class, TermTypeEnum::xterm);
    }

    public function testGetValue()
    {
        $this->assertEquals('ansi', TermTypeEnum::ansi->getValue());
        $this->assertEquals('dumb', TermTypeEnum::dumb->getValue());
        $this->assertEquals('hurd', TermTypeEnum::hurd->getValue());
        $this->assertEquals('pcansi', TermTypeEnum::pcansi->getValue());
        $this->assertEquals('linux', TermTypeEnum::linux->getValue());
        $this->assertEquals('xterm', TermTypeEnum::xterm->getValue());
        $this->assertEquals('xterm_r6', TermTypeEnum::xterm_r6->getValue());
        $this->assertEquals('vt100', TermTypeEnum::vt100->getValue());
        $this->assertEquals('vt102', TermTypeEnum::vt102->getValue());
        $this->assertEquals('vanilla', TermTypeEnum::vanilla->getValue());
    }

    public function testGetFromValue()
    {
        $this->assertEquals(TermTypeEnum::ansi, TermTypeEnum::ansi->getFromValue('ansi'));
        $this->assertEquals(TermTypeEnum::dumb, TermTypeEnum::ansi->getFromValue('dumb'));
        $this->assertEquals(TermTypeEnum::hurd, TermTypeEnum::ansi->getFromValue('hurd'));
        $this->assertEquals(TermTypeEnum::pcansi, TermTypeEnum::ansi->getFromValue('pcansi'));
        $this->assertEquals(TermTypeEnum::linux, TermTypeEnum::ansi->getFromValue('linux'));
        $this->assertEquals(TermTypeEnum::xterm, TermTypeEnum::ansi->getFromValue('xterm'));
        $this->assertEquals(TermTypeEnum::xterm_r6, TermTypeEnum::ansi->getFromValue('xterm_r6'));
        $this->assertEquals(TermTypeEnum::vt100, TermTypeEnum::ansi->getFromValue('vt100'));
        $this->assertEquals(TermTypeEnum::vt102, TermTypeEnum::ansi->getFromValue('vt102'));
        $this->assertEquals(TermTypeEnum::vanilla, TermTypeEnum::ansi->getFromValue('vanilla'));
    }
}
