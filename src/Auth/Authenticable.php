<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Auth;

interface Authenticable
{
    public function getAuthMethods(string $username): null|bool|array;

    public function authenticate(): self;

    public function getAuth(): ?AuthInterface;

    public function setAuth(AuthInterface $auth): self;

    public function isAuthorised(): bool;
}