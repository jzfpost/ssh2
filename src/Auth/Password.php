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

use JetBrains\PhpStorm\Pure;
use function ssh2_auth_password;

final class Password extends AbstractAuth
{

    #[Pure] public function __construct(
        string  $username,
        private readonly string $password
    )
    {
        parent::__construct($username);
    }

    /**
     * @inheritDoc
     */
    public function authenticate(mixed $session): bool
    {
        return ssh2_auth_password($session, $this->username, $this->password);
    }

}