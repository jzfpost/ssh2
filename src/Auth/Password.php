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

use function ssh2_auth_password;

final class Password extends AbstractAuth
{

    public function __construct(
        protected readonly string $username,
        private readonly string   $password
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function authenticate(mixed $session): bool
    {
        return ssh2_auth_password($session, $this->username, $this->password);
    }

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