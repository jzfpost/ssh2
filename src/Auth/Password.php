<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */
namespace jzfpost\ssh2\Auth;

use function ssh2_auth_password;

final class Password extends AbstractAuth
{
    private readonly string $password;

    /**
     * @inheritDoc
     */
    public function __construct(string $username, string $password)
    {
        parent::__construct($username);
        $this->password = $password;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(mixed $session): bool
    {
        return @ssh2_auth_password($session, $this->username, $this->password);
    }

    /**
     * @inheritDoc
     */
    public function setUsername(string $username): self
    {
        return new self($username, $this->password);
    }

    public function getPassword(): string
    {
        return '';
    }

    public function setPassword(string $password): self
    {
        return new self($this->username, $password);
    }
}