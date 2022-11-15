<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Auth;

use jzfpost\ssh2\TestCase;
use ReflectionException;

final class NoneTest extends TestCase
{
    protected string $username;
    protected None $auth;

    protected function setUp(): void
    {
        $this->username = 'admin';
        $this->auth = new None($this->username);
    }

    /**
     * @throws ReflectionException
     */
    public function testClass(): void
    {
        $this->assertInstanceOf('jzfpost\ssh2\Auth\None', $this->auth);
        $this->assertInstanceOf('jzfpost\ssh2\Auth\AuthInterface', $this->auth);

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
        $this->assertInstanceOf('jzfpost\ssh2\Auth\None', $new);
        $this->assertInstanceOf('jzfpost\ssh2\Auth\AuthInterface', $new);

        $this->assertIsString($new->getUsername());
        $this->assertNotEquals($new->getUsername(), $this->username);
        $this->assertEquals('user', $new->getUsername());


        $username = self::getUnaccessiblePropertyValue('username', $new);
        $this->assertIsString($username);
        $this->assertNotEquals($username, $this->username);
        $this->assertEquals('user', $username);
    }
}
