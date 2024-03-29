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

namespace jzfpost\ssh2\Conf;

use jzfpost\ssh2\SshException;

final class Callbacks
{
    /**
     * Function to call when an SSH2_MSG_IGNORE packet is received
     * @throws SshException
     */
    public static function ignore_cb(string $message): never
    {
        throw new SshException($message);
    }

    /**
     * Function to call when a packet is received but the message authentication code failed.
     * If the callback returns true, the mismatch will be ignored, otherwise the connection will be terminated
     */
    public static function macerror_cb(mixed $packet): bool
    {
        return true;
    }

    /**
     * Function to call when an SSH2_MSG_DEBUG packet is received
     * @throws SshException
     */
    public static function debug_cb(string $message, string $language, string $always_display): never
    {
        $msg = sprintf("Debug msg: %s\nLanguage: %s\nDisplay: %s\n", $message, $language, $always_display);

        throw new SshException($msg);
    }

    /**
     * Function to call when an SSH2_MSG_DISCONNECT packet is received
     * Notify the user if the connection terminates.
     * @throws SshException
     */
    public static function disconnect_cb(string $reason, string $message, string $language): never
    {
        $msg = sprintf("Server send disconnect message type [%d] and message: %s; lang: %s;\n", $reason, $message, $language);

        throw new SshException($msg);
    }
}