<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */
namespace jzfpost\ssh2\Conf;

use jzfpost\ssh2\TestCase;
use ReflectionException;

final class ConfigurationTest extends TestCase
{
    private Configuration $conf;
    private array $defaultConfiguration = [
        'host' => 'localhost',
        'port' => 22,
        'timeout' => 10,
        'wait' => 3500,
        'encoding' => false,
        'methods' => [],
        'callbacks' => [
            'ignore' => 'jzfpost\\Conf\\Callbacks::ignore_cb',
            'macerror' => 'jzfpost\\Conf\\Callbacks::macerror_cb',
            'disconnect' => 'jzfpost\\Conf\\Callbacks::disconnect_cb',
            'debug' => 'jzfpost\\Conf\\Callbacks::debug_cb'
        ],
        'termType' => TermTypeEnum::vanilla,
        'env' => null,
        'width' => SSH2_DEFAULT_TERM_WIDTH,
        'height' => SSH2_DEFAULT_TERM_HEIGHT,
        'widthHeightType' => WidthHeightTypeEnum::chars,
        'pty' => null
    ];

    private array $configuration = [
        'host' => '192.168.1.1',
        'port' => 44,
        'timeout' => 5,
        'wait' => 7000,
        'encoding' => 'UTF8',
        'methods' => [],
        'callbacks' => [
            'ignore' => 'jzfpost\\Conf\\Callbacks::ignore_cb',
            'macerror' => 'jzfpost\\Conf\\Callbacks::macerror_cb',
            'disconnect' => 'jzfpost\\Conf\\Callbacks::disconnect_cb',
            'debug' => 'jzfpost\\Conf\\Callbacks::debug_cb'
        ],
        'termType' => TermTypeEnum::xterm,
        'env' => null,
        'width' => 240,
        'height' => 240,
        'widthHeightType' => WidthHeightTypeEnum::pixels,
        'pty' => null
    ];

    protected function setUp(): void
    {
        $this->conf = new Configuration();
    }

    public function testClass(): void
    {
        /** @psalm-var string $className */
        $className = 'jzfpost\ssh2\Conf\Configuration';
        $this->assertInstanceOf($className, $this->conf);
    }

    public function testConstructor(): void
    {
        $new = new Configuration('192.168.1.1', 44);
        $this->assertEquals('192.168.1.1', $new->getHost());
        $this->assertEquals(44, $new->getPort());
    }

    public function testGetHost(): void
    {
        $this->assertEquals($this->defaultConfiguration['host'], $this->conf->getHost());
    }

    public function testGetPort(): void
    {
        $this->assertEquals($this->defaultConfiguration['port'], $this->conf->getPort());
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

    public function testGetEncoding(): void
    {
        $this->assertFalse($this->conf->getEncoding());
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
        $this->assertEquals($this->defaultConfiguration['widthHeightType'], $this->conf->getWidthHeightType());
    }

    public function testGetTermType(): void
    {
        $this->assertEquals($this->defaultConfiguration['termType'], $this->conf->getTermType());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetAsArray(): void
    {
        $this->assertEquals($this->defaultConfiguration, $this->conf->getAsArray());
    }

    public function testSetHost(): void
    {
        $new = (new Configuration())->setHost('192.168.1.1');
        $this->assertEquals('192.168.1.1', $new->getHost());
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

    public function testSetPort(): void
    {
        $new = (new Configuration())->setPort(7777);
        $this->assertEquals(7777, $new->getPort());
    }

    public function testSetEncoding(): void
    {
        $new = (new Configuration())->setEncoding('utf8');
        $this->assertEquals('utf8', $new->getEncoding());
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
        $this->assertEquals(WidthHeightTypeEnum::pixels, $new->getWidthHeightType());
    }

    public function testSetTermType(): void
    {
        $new = (new Configuration())->setTermType(TermTypeEnum::xterm);
        $this->assertEquals(TermTypeEnum::xterm, $new->getTermType());
    }

    /**
     * @throws ReflectionException
     */
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
