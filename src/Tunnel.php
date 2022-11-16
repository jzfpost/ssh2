<?php declare(strict_types=1);
/**
 * SSH2 tunnel helper class.
 *
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://giathub/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 */

namespace jzfpost\ssh2;

use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exceptions\SshException;

/**
 * USAGE:
 * ```php
 * $conf = (new Configuration('192.168.1.1'))
 *  ->setTermType('xterm')
 *  ->setLoggingFileName("/var/log/ssh2/log.txt")
 *  ->setDebugMode();
 *
 * $tunnelConf = (new Configuration('10.1.1.1'))
 *  ->setTermType('dump')
 *  ->setLoggingFileName("/var/log/ssh2/tunnel.log.txt")
 *  ->setDebugMode();
 *
 * $ssh2 = new Ssh($conf);
 * $tunnel = $ssh2->connect()
 *  ->authPassword($username, $password)
 *  ->getTunnel($tunnelConf);
 *
 * $exec = $tunnel->connect()
 *  ->authPassword('username', 'password')
 *  ->getExec();
 *
 * $result = $exec->send('ls -a');
 *
 * $tunnel->disconnect();
 * $ssh2->disconnect();
 * ```
 */
final class Tunnel extends AbstractSshObject
{
    private AbstractSshObject $ssh;

    public function __construct(AbstractSshObject $ssh, Configuration $configuration)
    {
        $this->ssh = $ssh;
        $this->configuration = $configuration;
    }

    public function connect(): self
    {
        $this->info('Trying start tunnel to host {host}:{port}');
        $this->session = ssh2_tunnel($this->ssh->getSession(), $this->host, $this->port);

        if (!$this->isConnected()) {
            $this->critical("Connection refused to host {host}:{port}");
            throw new SshException("Connection refused to host $this");
        }

        $this->info('Connection established success to host {host}:{port}');

        return $this;
    }
}