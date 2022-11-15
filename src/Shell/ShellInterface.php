<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail . com
 */

namespace jzfpost\ssh2\Shell;

use jzfpost\ssh2\PhpSsh2;

interface ShellInterface
{
    /**
     * @param string $prompt
     * @return bool
     */
    public function open(string $prompt): bool;
    public function close():void;

    /**
     * Send command and return output, which reading while $prompt will be read.
     * Output will return without first line
     * @param string $cmd
     * @param string $prompt
     * @return string
     */
    public function send(string $cmd, string $prompt): string;
}