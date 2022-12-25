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

namespace jzfpost\ssh2\Auth;

use jzfpost\ssh2\Session\SessionInterface;

abstract class AbstractAuth implements AuthInterface
{
    protected bool $isAuthorised = false;

    public function __construct(protected readonly string $username)
    {
    }

    abstract public function authenticate(SessionInterface $session): bool;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function isAuthorised(): bool
    {
        return $this->isAuthorised;
    }
}