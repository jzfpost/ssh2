<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Logger;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

interface SshLoggerAwareInterface extends LoggerAwareInterface
{
    public function getLogger(): LoggerInterface;

    public function getLogContext(): array;

}