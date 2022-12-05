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
use jzfpost\ssh2\Conf\Configurable;
use jzfpost\ssh2\Logger\SshLoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Stringable;

interface SshInterface extends Configurable, SshLoggerAwareInterface, Stringable
{
    public function connect(string $host = 'localhost', int $port = 22, LoggerInterface $logger = null): SshInterface;

    public function isConnected(): bool;

    public function disconnect(): void;

    /**
     * @return resource|false
     */
    public function getSession(): mixed;

    public function getMethodsNegotiated(): array;

    public function getFingerPrint(): string;

    public function getAuthMethods(string $username): null|bool|array;

    public function authentication(AuthInterface $auth): SshInterface;

    public function getAuth(): ?AuthInterface;

    public function isAuthorised(): bool;
}