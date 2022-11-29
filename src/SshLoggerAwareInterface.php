<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2;

use jzfpost\ssh2\Exceptions\SshException;
use Psr\Log\LoggerInterface;

interface SshLoggerAwareInterface extends \Psr\Log\LoggerAwareInterface
{
    public function getLogger(): LoggerInterface;

    public function getLogContext(): array;

    /**
     * @throws SshException
     */
    public function loggedException(string $message, array $context = []): never;

}