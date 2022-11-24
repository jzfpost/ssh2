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

final class Logger extends AbstractLogger implements LoggerInterface
{

    public readonly string $filePath;
    public bool $isPrintable;
    public string $dateFormat;

    public function __construct(string $filePath, bool $isPrintable = false, string $dateFormat = 'Y M d H:i:s')
    {
        $this->filePath = $filePath;
        $this->isPrintable = $isPrintable;
        $this->dateFormat = $dateFormat;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array())
    {
        /** @psalm-var array<array-key, float|int|string> $context */

        if (!is_string($level)) {
            $level = (string) $level;
        }

        $timestamp = date($this->dateFormat);

        if (strlen($message) > 1) {
            $message = ucfirst($message);
        }

        if ($level === 'none') {
            $text = $message;
        } elseif ($level === 'debug') {
            $text = sprintf('[%s] {host}:{port} %s:', $timestamp, $level)
                . PHP_EOL
                . '---------------- ---------------- ----------------' . PHP_EOL
                . $message
                . PHP_EOL
                . '================ ================ ================' . PHP_EOL;
        } else {
            $text = sprintf('[%s] {host}:{port} %s: %s', $timestamp, $level, $message) . PHP_EOL;
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