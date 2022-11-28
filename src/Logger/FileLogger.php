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

namespace jzfpost\ssh2\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

final class FileLogger extends AbstractLogger implements LoggerInterface
{

    public function __construct(
        public readonly string $filePath,
        public bool            $isPrintable = false,
        public string          $dateFormat = 'Y M d H:i:s'
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        /** @psalm-var array<array-key, float|int|string> $context */

        if (!is_string($level)) {
            $level = (string) $level;
        }

        $timestamp = date($this->dateFormat);

        $text = (string) $message;

        if (strlen($text) > 1) {
            $text = ucfirst($text);
        }

        if ($level === 'debug') {
            $text = PHP_EOL
                . sprintf('[%s] {host}:{port} %s:', $timestamp, $level)
                . PHP_EOL
                . '---------------- ---------------- ----------------' . PHP_EOL
                . $text
                . PHP_EOL
                . '================ ================ ================' . PHP_EOL
                . PHP_EOL;
        } else {
            $text = sprintf('[%s] {host}:{port} %s: %s', $timestamp, $level, $text) . PHP_EOL;
        }

        $text = str_replace(array_keys($context), $context, $text);

        if ($this->isPrintable) {
            print $text;
        }

        if (!file_exists($this->filePath)) {
            touch($this->filePath);
        }

        @file_put_contents($this->filePath, $text, FILE_APPEND);

    }
}