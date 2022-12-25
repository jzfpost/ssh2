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

namespace jzfpost\ssh2\Exec;

use jzfpost\ssh2\Conf\Configurable;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Session\SessionInterface;
use jzfpost\ssh2\SshException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function fclose;
use function fflush;
use function is_resource;
use function microtime;
use function ssh2_fetch_stream;
use function stream_get_contents;
use function stream_set_blocking;
use function stream_set_timeout;
use function usleep;

abstract class AbstractExec implements ExecInterface, Configurable
{
    /**
     * @var resource|false
     */
//    protected mixed $session;
    /**
     * @var resource|closed-resource|false
     */
    protected mixed $stderr = false;
    private float $executeTimestamp;

    public function __construct(
        public SessionInterface $session,
        public Configuration    $configuration = new Configuration(),
        public LoggerInterface  $logger = new NullLogger(),
        public array            $context = []
    )
    {
        if (!$this->session->isConnected()) {
            throw new SshException("Failed connection", $this->logger, $this->context);
        }

//        $this->session = $this->ssh->getSession();

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

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @psalm-return resource|false
     */
    public function getStderr(): mixed
    {
        return is_resource($this->stderr) ? $this->stderr : false;
    }

    /**
     * @param resource $stream
     */
    public function fetchStream(mixed $stream): void
    {
        $this->stderr = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($this->stderr, true);
        stream_set_blocking($stream, true);
    }

    /**
     * @param resource $stream
     */
    public function getStreamContent(mixed $stream): false|string
    {
        usleep($this->configuration->getWait());
        stream_set_timeout($stream, $this->configuration->getTimeout());
        $content = @stream_get_contents($stream);
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