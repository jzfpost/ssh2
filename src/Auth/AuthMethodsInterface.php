<?php

declare(strict_types=1);

namespace jzfpost\ssh2\Auth;

interface AuthMethodsInterface
{
    public function getAuthMethods(): array;
}