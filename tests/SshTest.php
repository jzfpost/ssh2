<?php

namespace jzfpost\ssh2;

use jzfpost\ssh2\Exec\Exec;
use jzfpost\ssh2\Exec\Shell;

class SshTest extends TestCase
{

    public function testSetLogger()
    {
 //       $ssh = $this->createMock();
    }

    public function testIsAuthorised()
    {
        $ssh = new Ssh();
        $this->assertFalse($ssh->isAuthorised());
    }

    public function testGetMethodNegotiated()
    {
        $ssh = new Ssh();
        $ssh->connect();
        $methods = $ssh->getMethodsNegotiated();
        $this->assertArrayHasKey('kex', $methods);
        $this->assertArrayHasKey('hostkey', $methods);
        $this->assertArrayHasKey('client_to_server', $methods);
        $this->assertArrayHasKey('server_to_client', $methods);
    }

    public function testConnect()
    {
        $ssh = new Ssh();
        $this->assertTrue($ssh->connect()->isConnected());
    }

    public function testGetExec()
    {
        $ssh = new Ssh();
        $exec = $ssh->getExec();
        $this->assertInstanceOf(Exec::class, $exec);
    }

    public function testGetShell()
    {
        $ssh = new Ssh();
        $shell = $ssh->getShell();
        $this->assertInstanceOf(Shell::class, $shell);
    }

    public function testDisconnect()
    {

    }

    public function testAuthPubkey()
    {

    }

    public function testGetConfiguration()
    {

    }

    public function testGetSession()
    {

    }

    public function test__get()
    {

    }

    public function testGetUsername()
    {

    }

    public function testAuthAgent()
    {

    }

    public function test__toString()
    {

    }

    public function testAuthentication()
    {

    }

    public function testAuthHostbased()
    {
    }

    public function testAuthPassword()
    {

    }

    public function testAuthNone()
    {

    }

    public function testGetAuthMethods()
    {

    }

    public function testGetFingerPrint()
    {

    }

    public function testGetLogger()
    {

    }

    public function testGetLogContext()
    {

    }

    public function test__construct()
    {

    }

    public function test__destruct()
    {

    }
}
