<?php declare(strict_types=1);
/**
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://github/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 */

namespace jzfpost\ssh2;

use jzfpost\ssh2\Auth\AuthInterface;
use jzfpost\ssh2\Conf\Configuration;
use Psr\Log\LoggerInterface;

interface SshInterface
{
    public function connect(): SshInterface;

    public function isConnected(): bool;

    public function disconnect(): void;

    public function authentication(AuthInterface $auth): SshInterface;

    public function getUsername(): string;

    public function isAuthorised(): bool;

    /**
     * @return resource|false
     */
    public function getSession(): mixed;

    public function getConfiguration(): Configuration;

    public function getLogger(): LoggerInterface;

    public function getLogContext(): array;

    public function getFingerPrint(): string;

    public function getMethodNegotiated(): array;

    public function __toString(): string;
}