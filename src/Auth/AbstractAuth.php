<?php
declare(strict_types=1);

namespace jzfpost\ssh2\Auth;

abstract class AbstractAuth implements AuthInterface
{
    protected readonly string $username;

    /**
     * @param string $username
     */
    public function __construct(string $username)
    {
        $this->username = $username;
    }

    /**
     * @inheritDoc
     */
    abstract public function authenticate(mixed $session): bool;

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return self
     */
    abstract public function setUsername(string $username): self;
}