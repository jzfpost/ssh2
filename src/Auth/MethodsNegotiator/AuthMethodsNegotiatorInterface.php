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

interface AuthMethodsNegotiatorInterface
{
    /**
     * Method will be called before the authentication
     */
    public function negotiate(SessionInterface $session, string $username): self;

    /**
     * Return an array of accepted authentication methods.
     * Will be calling after called $this->negotiate()
     */
    public function getAcceptedAuthMethods(): array;
}