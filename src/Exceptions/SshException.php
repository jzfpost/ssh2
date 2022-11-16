<?php declare(strict_types=1);
/**
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://giathub/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 * @see         "php -i | grep ssh2". Package tested with php-ssh ext-ssh2 version => 1.3.1 on libssh2 version => 1.8.0
 */

namespace jzfpost\ssh2\Exceptions;

use RuntimeException;

class SshException extends RuntimeException
{
}