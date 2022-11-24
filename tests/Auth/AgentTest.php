<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Auth;

use jzfpost\ssh2\TestCase;
use ReflectionException;
use jzfpost\ssh2\Auth\Agent;
use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Auth\AbstractAuth;

final class AgentTest extends TestCase
{

    protected string $username;
    protected Agent $auth;

    protected function setUp(): void
    {
        $this->username = 'admin';
        $this->auth = new Agent($this->username);
    }

    /**
     * @throws ReflectionException
     */
    public function testClass(): void
    {
        $this->assertInstanceOf(Agent::class, $this->auth);
        $this->assertInstanceOf(AuthInterface::class, $this->auth);

        $username = self::getUnaccessiblePropertyValue('username', $this->auth);
        $this->assertIsString($username);
        $this->assertEquals($username, $this->username);
    }

    /**
     * @throws ReflectionException
     */
    public function testSetUsername(): void
    {
        $new = $this->auth->setUsername('user');
        $this->assertInstanceOf(Agent::class, $new);
        $this->assertInstanceOf(AuthInterface::class, $new);
        $this->assertInstanceOf(AbstractAuth::class, $new);

        $this->assertIsString($new->getUsername());
        $this->assertNotEquals($new->getUsername(), $this->username);
        $this->assertEquals('user', $new->getUsername());


        $username = self::getUnaccessiblePropertyValue('username', $new);
        $this->assertIsString($username);
        $this->assertNotEquals($username, $this->username);
        $this->assertEquals('user', $username);
    }
}
