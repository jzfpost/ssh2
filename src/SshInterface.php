<?php declare(strict_types=1);
/**
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

use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Conf\Configuration;

interface SshInterface
{
    public function connect(): self;

    public function isConnected(): bool;

    public function disconnect(): void;

    public function authentication(AuthInterface $auth): self;

    public function getUsername(): string;

    /**
     * @return resource|closed-resource|false
     */
    public function getSession(): mixed;

    public function getConfiguration(): Configuration;

    public function getFingerPrint(): string;

    public function getMethodNegotiated(): array;
}