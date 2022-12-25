<?php

declare(strict_types=1);

namespace jzfpost\ssh2\Auth;

use jzfpost\ssh2\Session\SessionInterface;

final class AuthMethods implements AuthMethodsInterface
{
    private readonly array $authMethods;

    public function __construct(
        private readonly SessionInterface $session,
        private readonly string           $username
    )
    {
        $methods = @ssh2_auth_none($this->session->getSession(), $this->username);
        $this->authMethods = is_array($methods) ? $methods : [];
    }

    public function getAuthMethods(): array
    {
        return $this->authMethods;
    }
}