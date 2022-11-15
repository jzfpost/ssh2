<?php declare(strict_types=1);

namespace jzfpost\ssh2\Auth;

use function ssh2_auth_none;

final class None extends AbstractAuth
{

    /**
     * @inheritDoc
     */
    public function authenticate(mixed $session): bool
    {
        return @ssh2_auth_none($session, $this->username) === true;
    }

    /**
     * @inheritDoc
     */
    public function setUsername(string $username): self
    {
        return new self($username);
    }
}