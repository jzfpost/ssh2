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

namespace jzfpost\ssh2\Exec;

use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exceptions\SshException;
use jzfpost\ssh2\Ssh;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function assert;
use function fclose;
use function fflush;
use function is_resource;
use function microtime;
use function ssh2_fetch_stream;
use function stream_get_contents;
use function stream_set_blocking;
use function stream_set_timeout;
use function usleep;

abstract class AbstractExec implements ExecInterface
{
    private float $executeTimestamp;
    /**
     * @var resource|closed-resource|false errors
     */
    protected mixed $stderr = false;

    public function __construct(
        protected Ssh             $ssh,
        protected Configuration   $configuration = new Configuration(),
        protected LoggerInterface $logger = new NullLogger()
    )
    {
        $this->executeTimestamp = microtime(true);
    }

    abstract public function exec(string $cmd): string;

    public function close(): void
    {
        $stdErr = $this->getStderr();
        if (is_resource($stdErr)) {
            @fflush($stdErr);
            @fclose($stdErr);
        }
        $this->stderr = false;
    }

    /**
     * @psalm-return resource|false
     */
    public function getStderr(): mixed
    {
        return is_resource($this->stderr) ? $this->stderr : false;
    }

    protected function checkConnectionEstablished(): void
    {
        if (!$this->ssh->isConnected()) {
            $message = "Failed connection";
            $this->logger->critical($message, $this->ssh->getLogContext());
            throw new SshException($message);
        }
        if (false === $this->ssh->isAuthorised()) {
            $message = "Failed authorisation";
            $this->logger->critical($message, $this->ssh->getLogContext());
            throw new SshException($message);
        }
    }

    /**
     * @psalm-param resource $streem
     */
    protected function fetchStream(mixed $stream): void
    {
        assert(is_resource($stream));

        $this->stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($this->stderr, true);
        stream_set_blocking($stream, true);
    }

    protected function getStreamContent(mixed $stream): false|string
    {
        assert(is_resource($stream));

        usleep($this->configuration->getWait());
        stream_set_timeout($stream, $this->configuration->getTimeout());
        $content = stream_get_contents($stream);
        @fflush($stream);

        return $content;
    }

    protected function startTimer(): void
    {
        $this->executeTimestamp = microtime(true);
    }

    protected function stopTimer(): float
    {
        return microtime(true) - $this->executeTimestamp;
    }

}