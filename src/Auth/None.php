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
use function ssh2_auth_none;

final class None extends AbstractAuth implements AuthInterface
{
    public function authenticate(SessionInterface $session): bool
    {
        return $this->isAuthorised = @ssh2_auth_none($session->getConnection(), $this->username) === true;
    }
}