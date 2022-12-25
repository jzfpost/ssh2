<?php

declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Auth;

use jzfpost\ssh2\TestCase;
use ReflectionException;

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

}
