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

use function ssh2_auth_pubkey_file;

final class Pubkey extends AbstractAuth
{
    private string $pubkeyFile;
    private string $privkeyFile;
    private string $passphrase;

    /**
     * @inheritDoc
     */
    public function __construct(
        string $username,
        string $pubkeyFile,
        string $privkeyFile,
        string $passphrase = ''
    )
    {
        parent::__construct($username);
        $this->pubkeyFile = $pubkeyFile;
        $this->privkeyFile = $privkeyFile;
        $this->passphrase = $passphrase;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(mixed $session): bool
    {
        return ssh2_auth_pubkey_file(
            $session,
            $this->username,
            $this->pubkeyFile,
            $this->privkeyFile,
            $this->passphrase
        );
    }

    /**
     * @inheritDoc
     */
    public function setUsername(string $username): self
    {
        return new self($username, $this->pubkeyFile, $this->privkeyFile, $this->passphrase);
    }
}