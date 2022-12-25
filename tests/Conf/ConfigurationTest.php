<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Conf;

use jzfpost\ssh2\Methods\Methods;
use jzfpost\ssh2\TestCase;

final class ConfigurationTest extends TestCase
{
    private Configuration $conf;
    private array $defaultConfiguration;

    private array $configuration;

    protected function setUp(): void
    {
        $this->conf = new Configuration();
        $this->defaultConfiguration = [
            'timeout' => 10,
            'wait' => 3500,
            'methods' => null,
            'callbacks' => [
                'ignore' => 'jzfpost\\ssh2\\Conf\\Callbacks::ignore_cb',
                'macerror' => 'jzfpost\\ssh2\\Conf\\Callbacks::macerror_cb',
                'disconnect' => 'jzfpost\\ssh2\\Conf\\Callbacks::disconnect_cb',
                'debug' => 'jzfpost\\ssh2\\Conf\\Callbacks::debug_cb'
            ],
            'termType' => TermTypeEnum::vanilla,
            'env' => null,
            'width' => SSH2_DEFAULT_TERM_WIDTH,
            'height' => SSH2_DEFAULT_TERM_HEIGHT,
            'widthHeightType' => WidthHeightTypeEnum::chars,
            'fingerPrintAlgorithm' => FPAlgorithmEnum::md5
        ];

        $this->configuration = [
            'timeout' => 5,
            'wait' => 7000,
            'methods' => new Methods(),
            'callbacks' => null,
            'termType' => TermTypeEnum::xterm,
            'env' => [
                'test' => 'test',
            ],
            'width' => 240,
            'height' => 240,
            'widthHeightType' => WidthHeightTypeEnum::pixels,
            'fingerPrintAlgorithm' => FPAlgorithmEnum::sha1
        ];
    }

    public function testClass(): void
    {
        /** @psalm-var string $className */
        $className = 'jzfpost\ssh2\Conf\Configuration';
        $this->assertInstanceOf($className, $this->conf);
    }

    public function testGetTimeout(): void
    {
        $this->assertEquals($this->defaultConfiguration['timeout'], $this->conf->getTimeout());
    }

    public function testGetWait(): void
    {
        $this->assertEquals($this->defaultConfiguration['wait'], $this->conf->getWait());
    }

    public function testGetEnv(): void
    {
        $this->assertEquals($this->defaultConfiguration['env'], $this->conf->getEnv());
    }

    public function testGetWidth(): void
    {
        $this->assertEquals($this->defaultConfiguration['width'], $this->conf->getWidth());
    }

    public function testGetHeight(): void
    {
        $this->assertEquals($this->defaultConfiguration['height'], $this->conf->getHeight());
    }

    public function testGetWidthHeightType(): void
    {
        $this->assertEquals($this->defaultConfiguration['widthHeightType'], $this->conf->getWidthHeightTypeEnum());
        $this->assertEquals(WidthHeightTypeEnum::chars, $this->conf->getWidthHeightTypeEnum());
        $this->assertEquals(WidthHeightTypeEnum::chars->getValue(), $this->conf->getWidthHeightType());
    }

    public function testGetTermType(): void
    {
        $this->assertEquals($this->defaultConfiguration['termType'], $this->conf->getTermTypeEnum());
        $this->assertEquals(TermTypeEnum::vanilla, $this->conf->getTermTypeEnum());
        $this->assertEquals(TermTypeEnum::vanilla->getValue(), $this->conf->getTermType());
    }

    public function testGetAsArray(): void
    {
        $this->assertEquals($this->defaultConfiguration, $this->conf->getAsArray());
    }

    public function testGetCallbacks(): void
    {
        $this->assertEquals($this->defaultConfiguration['callbacks'], $this->conf->getCallbacks());
    }

    public function testSetEnv(): void
    {
        $new = (new Configuration())->setEnv(['env' => 'test']);
        $this->assertEquals(['env' => 'test'], $new->getEnv());
    }

    public function testSetWait(): void
    {
        $new = (new Configuration())->setWait(7000);
        $this->assertEquals(7000, $new->getWait());
    }

    public function testSetTimeout(): void
    {
        $new = (new Configuration())->setTimeout(50);
        $this->assertEquals(50, $new->getTimeout());
    }

    public function testSetWidth(): void
    {
        $new = (new Configuration())->setWidth(50);
        $this->assertEquals(50, $new->getWidth());
    }

    public function testSetHeight(): void
    {
        $new = (new Configuration())->setHeight(50);
        $this->assertEquals(50, $new->getHeight());
    }

    public function testSetWidthHeightType(): void
    {
        $new = (new Configuration())->setWidthHeightType(WidthHeightTypeEnum::pixels);
        $this->assertEquals(WidthHeightTypeEnum::pixels->getValue(), $new->getWidthHeightType());
        $this->assertEquals(WidthHeightTypeEnum::pixels, $new->getWidthHeightTypeEnum());
    }

    public function testSetTermType(): void
    {
        $new = (new Configuration())->setTermType(TermTypeEnum::xterm);
        $this->assertEquals(TermTypeEnum::xterm->getValue(), $new->getTermType());
        $this->assertEquals(TermTypeEnum::xterm, $new->getTermTypeEnum());
    }

    public function testSetMethods(): void
    {
        $methods = new Methods();
        $new = (new Configuration())->setMethods($methods);
        $this->assertEquals($methods, $new->getMethodsObject());
        $this->assertEquals($methods->asArray(), $new->getMethods());
    }

    public function testSetCallbacks(): void
    {
        $callbacks = null;
        $new = (new Configuration())->setCallbacks($callbacks);
        $this->assertEquals($callbacks, $new->getCallbacks());
    }

    public function testSetFromArray(): void
    {
        $new = (new Configuration())->setFromArray($this->configuration);
        $this->assertEquals($this->configuration, $new->getAsArray());
    }

    public function testGetDefaultProperties(): void
    {
        $this->assertEquals($this->defaultConfiguration, $this->conf->getDefaultProperties());
    }
}
