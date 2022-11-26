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

namespace jzfpost\ssh2\Exec;

interface ShellInterface extends ExecInterface
{
    /**
     * @param string $prompt
     * @return ShellInterface
     */
    public function open(string $prompt): ShellInterface;

    public function isOpened(): bool;

    /**
     * Send command and return output, which reading while $prompt will be read.
     * Output will return without first line
     * @param string $cmd
     * @param string $prompt
     * @return string
     */
    public function send(string $cmd, string $prompt): string;

}