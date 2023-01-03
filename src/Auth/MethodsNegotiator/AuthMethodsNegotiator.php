<?php

declare(strict_types=1);
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

namespace jzfpost\ssh2\Auth\MethodsNegotiator;

use jzfpost\ssh2\Session\SessionInterface;

final class AuthMethodsNegotiator implements AuthMethodsNegotiatorInterface
{
    private array $authMethods = [];

    public function negotiate(SessionInterface $session, string $username): self
    {
        $methods = @ssh2_auth_none($session->getConnection(), $username);
        $this->authMethods = is_array($methods) ? $methods : [];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAcceptedAuthMethods(): array
    {
        return $this->authMethods;
    }
}