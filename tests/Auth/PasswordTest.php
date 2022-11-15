<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Auth;

use jzfpost\ssh2\TestCase;
use ReflectionException;

final class PasswordTest extends TestCase
{
    protected string $username;
    protected string $password;
    protected Password $auth;

    protected function setUp(): void
    {
        $this->username = 'admin';
        $this->password = 'password';

        $this->auth = new Password($this->username, $this->password);
    }

    /**
     * @throws ReflectionException
     */
    public function testClass(): void
    {
        $this->assertInstanceOf('jzfpost\ssh2\Auth\Password', $this->auth);
        $this->assertInstanceOf('jzfpost\ssh2\Auth\AuthInterface', $this->auth);

        $username = self::getUnaccessiblePropertyValue('username', $this->auth);
        $this->assertIsString($username);
        $this->assertEquals($username, $this->username);

        $password = self::getUnaccessiblePropertyValue('password', $this->auth);
        $this->assertIsString($password);
        $this->assertEquals($password, $this->password);

    }

    public function testGetUsername(): void
    {
        $this->assertIsString($this->auth->getUsername());
        $this->assertEquals($this->auth->getUsername(), $this->username);
    }

    public function testGetPassword(): void
    {
        $this->assertIsString($this->auth->getPassword());
        $this->assertNotEquals($this->auth->getPassword(), $this->username);
        $this->assertNotEquals($this->auth->getPassword(), $this->password);
        $this->assertEquals('', $this->auth->getPassword());
    }

    /**
     * @throws ReflectionException
     */
    public function testSetUsername(): void
    {
        $new = $this->auth->setUsername('user');
        $this->assertInstanceOf('jzfpost\ssh2\Auth\Password', $new);
        $this->assertInstanceOf('jzfpost\ssh2\Auth\AuthInterface', $new);

        $this->assertIsString($new->getUsername());
        $this->assertNotEquals($new->getUsername(), $this->username);
        $this->assertEquals('user', $new->getUsername());

        $this->assertIsString($this->auth->getPassword());

        $username = self::getUnaccessiblePropertyValue('username', $new);
        $this->assertIsString($username);
        $this->assertNotEquals($username, $this->username);
        $this->assertEquals('user', $username);

        $password = self::getUnaccessiblePropertyValue('password', $new);
        $this->assertIsString($password);
        $this->assertEquals($password, $this->password);
    }

    /**
     * @throws ReflectionException
     */
    public function testSetPassword(): void
    {
        $new = $this->auth->setPassword('12345-+$');
        $this->assertInstanceOf('jzfpost\ssh2\Auth\Password', $new);
        $this->assertInstanceOf('jzfpost\ssh2\Auth\AuthInterface', $new);

        $this->assertIsString($this->auth->getPassword());
        $this->assertNotEquals($this->auth->getPassword(), $this->username);
        $this->assertNotEquals($this->auth->getPassword(), $this->password);

        $this->assertEquals('', $this->auth->getPassword());

        $username = self::getUnaccessiblePropertyValue('username', $new);
        $this->assertIsString($username);
        $this->assertEquals($username, $this->username);

        $password = self::getUnaccessiblePropertyValue('password', $new);
        $this->assertIsString($password);
        $this->assertNotEquals($password, $this->password);
        $this->assertEquals('12345-+$', $password);
    }

}
