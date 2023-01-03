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

namespace jzfpost\ssh2\Logger;

use DateTimeImmutable;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;

final class RealtimeLogger extends AbstractLogger implements LoggerInterface
{

    public function __construct(public string $dateFormat = 'Y M d H:i:s')
    {
    }

    /**
     * @inheritDoc
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        /** @psalm-var array<string, float|int|string> $context */

        if (!is_string($level)) {
            $level = (string) $level;
        }

        $timestamp = $this->now()->format($this->dateFormat);

        $text = print_r($message, true);

        if ($level === 'debug') {
            $text = sprintf('[%s] {host}:{port} %s:', $timestamp, $level)
                . PHP_EOL
                . '---------------- ---------------- ----------------' . PHP_EOL
                . $text
                . PHP_EOL
                . '================ ================ ================' . PHP_EOL
                . PHP_EOL;
        } else {
            if (strlen($text) > 1) {
                $text = ucfirst($text);
            }
            $text = sprintf('[%s] {host}:{port} %s: %s', $timestamp, $level, $text) . PHP_EOL;
        }

        print str_replace(array_keys($context), $context, $text);
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}