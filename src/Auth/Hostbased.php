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

namespace jzfpost\ssh2\Auth;

use function ssh2_auth_hostbased_file;

final class Hostbased extends AbstractAuth
{

    public function __construct(
        protected readonly string $username,
        private readonly string   $hostname,
        private readonly string   $pubkeyFile,
        private readonly string   $privkeyFile,
        private readonly string   $passphrase = '',
        private readonly string   $localUsername = ''
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function authenticate(mixed $session): bool
    {
        return ssh2_auth_hostbased_file(
            $session,
            $this->username,
            $this->hostname,
            $this->pubkeyFile,
            $this->privkeyFile,
            $this->passphrase,
            $this->localUsername
        );
    }

    public function setUsername(string $username): self
    {
        return new self(
            $username,
            $this->hostname,
            $this->pubkeyFile,
            $this->privkeyFile,
            $this->passphrase,
            $this->localUsername
        );
    }

}