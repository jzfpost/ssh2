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

namespace jzfpost\ssh2\Auth;

use function ssh2_auth_hostbased_file;

final class Hostbased extends AbstractAuth
{
    private string $hostname;
    private string $pubkeyFile;
    private string $privkeyFile;
    private string $passphrase;
    private string $localUsername;

    /**
     * @inheritDoc
     */
    public function __construct(
        string $username,
        string $hostname,
        string $pubkeyFile,
        string $privkeyFile,
        string $passphrase = '',
        string $localUsername = ''
    )
    {
        parent::__construct($username);
        $this->hostname = $hostname;
        $this->pubkeyFile = $pubkeyFile;
        $this->privkeyFile = $privkeyFile;
        $this->passphrase = $passphrase;
        $this->localUsername = $localUsername;
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

    /**
     * @inheritDoc
     */
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