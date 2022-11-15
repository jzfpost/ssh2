<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */
namespace jzfpost\ssh2\Auth;

interface AuthInterface
{
    /**
     * @param resource $session
     * @return bool
     */
    public function authenticate(mixed $session): bool;

    /**
     * @return string
     */
    public function getUsername(): string;
}