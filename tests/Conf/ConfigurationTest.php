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
        'env' => [
            0 => null
        ],
        'timeout' => 10,
        'wait' => 3500,
        'encoding' => false,
        'loggingFileName' => false,
        'debugMode' => false,
        'dateFormat' => 'Y M d H:i:s',
        'methods' => [],
        'callbacks' => [
            'ignore' => 'jzfpost\\Exception\\Callback::ignore_cb',
            'macerror' => 'jzfpost\\Exception\\Callback::macerror_cb',
            'disconnect' => 'jzfpost\\Exception\\Callback::disconnect_cb',
            'debug' => 'jzfpost\\Exception\\Callback::debug_cb'
        ]
    ];

    private array $configuration = [
        'host' => '192.168.1.1',
        'port' => 44,
        'env' => [
            0 => null
        ],
        'timeout' => 5,
        'wait' => 7000,
        'encoding' => 'UTF8',
        'loggingFileName' => '/var/log/ssh2/log.txt',
        'debugMode' => true,
        'dateFormat' => 'H:i:s d M Y',
        'methods' => [],
        'callbacks' => [
            'ignore' => 'jzfpost\\Exception\\Callback::ignore_cb',
            'macerror' => 'jzfpost\\Exception\\Callback::macerror_cb',
            'disconnect' => 'jzfpost\\Exception\\Callback::disconnect_cb',
            'debug' => 'jzfpost\\Exception\\Callback::debug_cb'
        ]
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
        $this->assertIsString($this->conf->getHost());
        $this->assertNotEmpty($this->conf->getHost());
        $this->assertEquals($this->defaultConfiguration['host'], $this->conf->getHost());
    }

    public function testGetPort(): void
    {
        $this->assertIsInt($this->conf->getPort());
        $this->assertNotEmpty($this->conf->getPort());
        $this->assertEquals($this->defaultConfiguration['port'], $this->conf->getPort());
    }

    public function testGetDateFormat(): void
    {
        $this->assertIsString($this->conf->getDateFormat());
        $this->assertNotEmpty($this->conf->getDateFormat());
        $this->assertEquals($this->defaultConfiguration['dateFormat'], $this->conf->getDateFormat());
    }

    public function testGetTimeout(): void
    {
        $this->assertIsInt($this->conf->getTimeout());
        $this->assertNotEmpty($this->conf->getTimeout());
        $this->assertEquals($this->defaultConfiguration['timeout'], $this->conf->getTimeout());
    }

    public function testIsDebugMode(): void
    {
        $this->assertFalse($this->conf->isDebugMode());
        $this->assertEquals($this->defaultConfiguration['debugMode'], $this->conf->isDebugMode());
    }

    public function testGetWait(): void
    {
        $this->assertIsInt($this->conf->getWait());
        $this->assertNotEmpty($this->conf->getWait());
        $this->assertEquals($this->defaultConfiguration['wait'], $this->conf->getWait());
    }

    public function testGetEnv(): void
    {
        $this->assertIsArray($this->conf->getEnv());
        $this->assertEquals($this->defaultConfiguration['env'], $this->conf->getEnv());
    }

    public function testGetEncoding(): void
    {
        $this->assertFalse($this->conf->getEncoding());
    }

    public function testGetLoggingFileName(): void
    {
        $this->assertFalse($this->conf->getLoggingFileName());
    }

    /**
     * @throws ReflectionException
     */
    public function testGetAsArray(): void
    {
        $this->assertIsArray($this->conf->getAsArray());
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

    public function testSetLoggingFileName(): void
    {

    }

    public function testSetDateFormat(): void
    {

    }

    public function testSetPort(): void
    {

    }

    public function testSetDebugMode(): void
    {

    }

    public function testSetEncoding(): void
    {

    }

    public function testSetTimeout(): void
    {

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
        $this->assertIsArray($this->conf->getDefaultProperties());
        $this->assertEquals($this->defaultConfiguration, $this->conf->getDefaultProperties());
    }
}
