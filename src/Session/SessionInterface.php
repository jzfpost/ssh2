<?php

declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Session;

use jzfpost\ssh2\Conf\FPAlgorithmEnum;

interface SessionInterface
{
    /**
     * @param string $host
     * @param positive-int $port
     */
    public function connect(string $host = 'localhost', int $port = 22): self;

    public function isConnected(): bool;

    public function disconnect(): void;

    /**
     * @return resource
     */
    public function getSession(): mixed;

    public function getMethodsNegotiated(): array;

    public function getFingerPrint(FPAlgorithmEnum $algorithm = FPAlgorithmEnum::md5): string;
}