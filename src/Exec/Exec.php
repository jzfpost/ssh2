<?php declare(strict_types=1);
/**
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://giathub/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 */

namespace jzfpost\ssh2\Exec;

use jzfpost\ssh2\AbstractSshObject;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exceptions\SshException;
use jzfpost\ssh2\Ssh;
use Psr\Log\LoggerTrait;

final class Exec implements ExecInterface
{
    use LoggerTrait;

    private AbstractSshObject $ssh;
    private Configuration $configuration;
    /**
     * @var resource|closed-resource|false errors
     */
    private mixed $stderr = false;
    private ?float $executeTimestamp = null;

    public function __construct(AbstractSshObject $ssh)
    {
        $this->ssh = $ssh;
        $this->configuration = $this->ssh->getConfiguration();

        $this->info("{property} set to {value}", ['{property}' => 'TERMTYPE', '{value}' => $this->configuration->getTermType()]);
        $this->info("{property} set to {value}", ['{property}' => 'WIDTH', '{value}' => (string)$this->configuration->getWidth()]);
        $this->info("{property} set to {value}", ['{property}' => 'HEIGHT', '{value}' => (string)$this->configuration->getHeight()]);

        foreach ($this->configuration->getEnv() as $key => $value) {
            if ($value === null) {
                $this->info("{property} set to {value}", ['{property}' => 'ENV', '{value}' => 'NULL']);
            } else {
                $this->info("{property} set to {value}", ['{property}' => 'ENV', '{value}' => $key . ' => ' . $value]);
            }
        }
    }

    public function exec(string $cmd): string|false
    {
        if (!$this->ssh->isConnected()) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new SshException("Failed connecting to host $this->ssh");
        }
        if (false === $this->ssh->isAuthorised) {
            $this->critical("Failed authorisation on host {host}:{port}");
            throw new SshException("Failed authorisation on host $this->ssh");
        }

        $this->info("Trying execute '{cmd}' at {host}:{port} connection", ['{cmd}' => $cmd]);

        $this->executeTimestamp = microtime(true);

        $exec = @ssh2_exec(
            $this->ssh->getSession(),
            $cmd,
            $this->configuration->getPty(),
            $this->configuration->getEnv(),
            $this->configuration->getWidth(),
            $this->configuration->getHeight(),
            $this->configuration->getWidthHeightType()
        );

        $this->stderr = ssh2_fetch_stream($exec, SSH2_STREAM_STDERR);
        stream_set_blocking($this->stderr, true);
        stream_set_blocking($exec, true);
        $content = stream_get_contents($exec);
        $this->info("Command execution time is {timestamp} msec", ['{timestamp}' => (string)(microtime(true) - $this->executeTimestamp)]);
        $this->debug($content);
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array()): void
    {
        $this->ssh->log($level, $message, $context);
    }
}