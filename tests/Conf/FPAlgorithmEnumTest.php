<?php
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf;

use PHPUnit\Framework\TestCase;

class FPAlgorithmEnumTest extends TestCase
{

    public function testTypeInterface()
    {
        $this->assertInstanceOf(IntEnumInterface::class, FPAlgorithmEnum::md5);
    }

    public function testGetFromValue()
    {
        $this->assertEquals(SSH2_FINGERPRINT_MD5, FPAlgorithmEnum::md5->getValue());
        $this->assertEquals(SSH2_FINGERPRINT_RAW, FPAlgorithmEnum::raw->getValue());
        $this->assertEquals(SSH2_FINGERPRINT_SHA1, FPAlgorithmEnum::sha1->getValue());
    }

    public function testGetValue()
    {
        $this->assertEquals(FPAlgorithmEnum::md5, FPAlgorithmEnum::md5->getFromValue(SSH2_FINGERPRINT_MD5));
        $this->assertEquals(FPAlgorithmEnum::raw, FPAlgorithmEnum::md5->getFromValue(SSH2_FINGERPRINT_RAW));
        $this->assertEquals(FPAlgorithmEnum::sha1, FPAlgorithmEnum::md5->getFromValue(SSH2_FINGERPRINT_SHA1));
    }
}
