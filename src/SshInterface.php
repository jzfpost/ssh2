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

namespace jzfpost\ssh2;

use Psr\Log\LoggerInterface;

interface SshInterface
{
    /**
     * @param non-empty-string $host
     * @param positive-int $port
     * @param LoggerInterface|null $logger
     * @return $this
     */
    public function connect(string $host = 'localhost', int $port = 22, LoggerInterface $logger = null): self;

    public function isConnected(): bool;

    public function disconnect(): void;

    /**
     * @return resource|false
     */
    public function getSession(): mixed;

    public function getCryptMethodsNegotiated(): array;

    public function getFingerPrint(): string;
}