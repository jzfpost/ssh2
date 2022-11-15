<?php
declare(strict_types=1);

namespace jzfpost\ssh2\Exception;

final class Callback
{
    /**
     * Function to call when an SSH2_MSG_IGNORE packet is received
     * @param string $message
     * @throws Ssh2Exception
     */
    public static function ignore_cb(string $message): void
    {
        throw new Ssh2Exception($message);
    }

    /**
     * Function to call when a packet is received but the message authentication code failed.
     * If the callback returns true, the mismatch will be ignored, otherwise the connection will be terminated
     * @param mixed $packet
     * @return true
     */
    public static function macerror_cb(mixed $packet): bool
    {
        return true;
    }

    /**
     * Function to call when an SSH2_MSG_DEBUG packet is received
     * @param string $message
     * @param string $language
     * @param string $always_display
     * @throws Ssh2Exception
     */
    public static function debug_cb(string $message, string $language, string $always_display): void
    {
        $msg = sprintf("Debug msg: %s\nLanguage: %s\nDisplay: %s\n", $message, $language, $always_display);

        throw new Ssh2Exception($msg);
    }

    /**
     * Function to call when an SSH2_MSG_DISCONNECT packet is received
     * Notify the user if the connection terminates.
     *
     * @param string $reason
     * @param string $message
     * @param string $language
     * @throws Ssh2Exception
     */
    public static function disconnect_cb(string $reason, string $message, string $language): void
    {
        $msg = sprintf("Server send disconnect message type [%d] and message: %s; lang: %s;\n", $reason, $message, $language);

        throw new Ssh2Exception($msg);
    }
}