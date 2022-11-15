<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */
namespace jzfpost\ssh2\Auth;

use function ssh2_auth_agent;

final class Agent extends AbstractAuth
{
    /**
     * @inheritDoc
     */
    public function authenticate(mixed $session): bool
    {
        return @ssh2_auth_agent($session, $this->username);
    }

    /**
     * @inheritDoc
     */
    public function setUsername(string $username): self
    {
        return new self($username);
    }
}