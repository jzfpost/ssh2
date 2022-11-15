<?php declare(strict_types=1);

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
        return @ssh2_auth_pubkey_file(
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