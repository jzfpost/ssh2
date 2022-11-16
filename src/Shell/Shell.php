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

namespace jzfpost\ssh2\Shell;

use jzfpost\ssh2\AbstractSshObject;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exceptions\SshException;
use jzfpost\ssh2\Ssh;
use Psr\Log\LoggerTrait;

final class Shell implements ShellInterface
{
    use LoggerTrait;

    /**
     * Commands turn off pagination on terminal
     */
    public const TERMINAL_PAGINATION_OFF_CISCO = 'terminal length 0';
    public const TERMINAL_PAGINATION_OFF_HUAWEI = 'screen-length 0 temporary';

    /**
     * RegExp prompts
     */
    public const PROMPT_LINUX = '{username}@[^:]+:~\$';
    public const PROMPT_LINUX_SU = 'root@[^:]+:[^#]+#';
    public const PROMPT_CISCO = '[\w._-]+[#>]';
    public const PROMPT_HUAWEI = '[[<]~?[\w._-]+[]>]';

    private AbstractSshObject $ssh;
    private Configuration $configuration;
    /**
     * @var resource|closed-resource|false errors
     */
    private mixed $stderr = false;
    /**
     * @var resource|closed-resource|false shell
     */
    private mixed $shell = false;
    private string $buffer = '';
    private ?float $executeTimestamp = null;

    /**
     * These are telnet options characters that might be of use for us.
     */
    private string $_NULL;
    private string $_DC1;
    private string $_WILL;
    private string $_WONT;
    private string $_DO;
    private string $_DONT;
    private string $_IAC;
    private string $_ESC;

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

        $this->_NULL = chr(0);
        $this->_DC1 = chr(17);
        $this->_WILL = chr(251);
        $this->_WONT = chr(252);
        $this->_DO = chr(253);
        $this->_DONT = chr(254);
        $this->_IAC = chr(255);
        $this->_ESC = chr(27);
    }

    /**
     * @inheritDoc
     */
    public function open(string $prompt): ShellInterface
    {
        if (is_resource($this->shell)) {
            throw new SshException("Already opened shell at $this->ssh connection");
        }
        if (!$this->ssh->isConnected()) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new SshException("Failed connecting to host $this->ssh");
        }
        if (false === $this->ssh->isAuthorised) {
            $this->critical("Failed authorisation on host {host}:{port}");
            throw new SshException("Failed authorisation on host $this->ssh");
        }

        $this->info('Trying opening shell at {host}:{port} connection');

        if ($this->ssh->isConnected()) {

            $this->shell = @ssh2_shell(
                $this->ssh->getSession(),
                $this->configuration->getTermType(),
                $this->configuration->getEnv(),
                $this->configuration->getWidth(),
                $this->configuration->getHeight(),
                $this->configuration->getWidthHeightType()
            );

            if ($this->isOpened()) {

                $this->info('Shell opened success at {host}:{port} connection');

                $this->stderr = ssh2_fetch_stream($this->shell, SSH2_STREAM_STDERR);
                stream_set_blocking($this->stderr, true);
                stream_set_blocking($this->shell, true);

                $this->readTo($prompt);
                $this->clearBuffer();

                return $this;
            }
        }
        $this->critical("Unable to establish shell at {host}:{port} connection");
        throw new SshException("Unable to establish shell at $this->ssh connection");
    }

    public function isOpened(): bool
    {
        return is_resource($this->shell);
    }

    public function close(): void
    {
        $this->__destruct();
    }

    /**
     * @inheritDoc
     */
    public function send(string $cmd, string $prompt): string
    {
        $this->write($cmd);
        $this->readTo($prompt);

        $buffer = $this->trimFirstLine(trim($this->buffer));
        $buffer = $this->trimPrompt($buffer, $prompt);

        return utf8_encode($buffer);
    }

    /**
     * Write command to a socket.
     *
     * @param string $cmd Stuff to write to socket
     * @return void
     * @throws SshException
     */
    private function write(string $cmd): void
    {
        $this->clearBuffer();

        if (!$this->isOpened()) {
            $this->critical("Unable to establish shell at {host}:{port} connection");
            throw new SshException("Unable to establish shell at $this->ssh connection");
        }
        $this->info('Write command to host {host}:{port} => "{cmd}"', ['{cmd}' => $cmd]);

        $this->executeTimestamp = microtime(true);

        if ((!fwrite($this->shell, trim($cmd) . PHP_EOL)) < 0) {
            $this->critical("Error writing to shell at {host}:{port} connection");
            throw new SshException("Error writing to shell at $this->ssh connection");
        }
    }

    /**
     * Reads characters from the shell and adds them to command buffer.
     * Handles telnet control characters. Stops when prompt is encountered.
     *
     * @param string $prompt
     * @return void
     * @throws SshException
     */
    private function readTo(string $prompt): void
    {
        $prompt = str_replace('{username}', $this->ssh->getUsername(), $prompt);

        $this->info('Set prompt to "{prompt}" on shell at {host}:{port} connection', ['{prompt}' => $prompt]);

        if (false === $this->ssh->isConnected()) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new SshException("Failed connecting to host $this->ssh");
        }

        if (!is_resource($this->shell)) {
            $this->critical("Unable to establish shell at {host}:{port} connection");
            throw new SshException("Unable to establish shell at $this->ssh connection");
        }

        $this->clearBuffer();

        usleep($this->configuration->getWait());

        $time = time() + $this->configuration->getTimeout();

        do {
            $c = @fgetc($this->shell);
            if (false === $c) {
                $this->info("Couldn't find the requested : '{prompt}', it was not in the data returned from server : '{buffer}'", ['{prompt}' => $prompt, '{buffer}' => $this->buffer]);
                throw new SshException("Couldn't find the requested : '$prompt', it was not in the data returned from server : '$this->buffer'");
            }

            // IANA TELNET OPTIONS
            if ($this->negotiateTelnetOptions($c)) {
                continue;
            }

            if ($this->configuration->getEncoding()) {
                $c = mb_convert_encoding($c, $this->configuration->getEncoding());
            }

            $this->buffer .= $c;

//            $this->log('none', $c);

            if (preg_match("/$prompt\s?$/i", $this->buffer)) {
                $this->log('none', PHP_EOL);
                if (is_float($this->executeTimestamp)) {
                    $this->info("Command execution time is {timestamp} msec", ['{timestamp}' => (string)(microtime(true) - $this->executeTimestamp)]);
                }
//                $this->history .= $this->buffer;
                $this->debug($this->buffer);
                break;
            }

            if ($time < time()) {
                $this->log('none', PHP_EOL);
//                $this->history .= $this->buffer;
                $this->debug($this->buffer);
                $this->info("Timeout released before the prompt was read on shell at {host}:{port} connection");
                break;
            }
        } while ($c !== $this->_NULL || $c !== $this->_DC1);

        $this->info("Data transmission is over on shell at {host}:{port} connection");
    }

    /**
     * Telnet control character magic.
     *
     * @param string $c
     * @return bool
     */
    private function negotiateTelnetOptions(string $c): bool
    {
        switch ($c) {
            case $this->_IAC:
                $this->debug("_IAC command was received");
                return true;
            case $this->_DO:
                $this->debug("_DO command was received");
                break;
            case $this->_DONT:
                $this->debug("_DONT command was received");
                break;
            case $this->_WILL:
                $this->debug("_WILL command was received");
                break;
            case $this->_WONT:
                $this->debug("_WONT command was received");
                break;
            case $this->_ESC:
                $this->debug("_ESC command was received");
                break;
            default:
                return false;
        }
        if (is_resource($this->shell)) {
            $opt = fgetc($this->shell);
            $this->debug("Shell option: " . $opt);
        }
        return true;
    }

    /**
     * Trim the first line of multiline text
     * @param string $text
     * @return string
     */
    private function trimFirstLine(string $text): string
    {
        return substr($text, (int)strpos($text, PHP_EOL, 1) + 1);
    }

    /**
     * Trim the prompt string of multiline text
     * @param string $text
     * @param string $prompt
     * @return string
     */
    private function trimPrompt(string $text, string $prompt): string
    {
        return preg_replace("/$prompt\s*$/i", '', $text);
    }

    /**
     * Returns the content of the command buffer.
     *
     * @return string Content of the command buffer
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     *
     * @return false|string
     */
    public function getStderr(): false|string
    {
        if (is_resource($this->stderr)) {
            return fgets($this->stderr, 8192);
        }
        return false;
    }

    /**
     * Clears internal command buffer.
     *
     * @return void
     */
    private function clearBuffer(): void
    {
        $this->buffer = '';
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array()): void
    {
        $this->ssh->log($level, $message, $context);
    }

    /**
     * Destructor.
     * @return void
     */
    public function __destruct()
    {
        $this->clearBuffer();
        if ($this->isOpened()) {
            if (!@fclose($this->shell)) {
                $this->critical('Shell stream closes is fail.');
            }
        }

        if (is_resource($this->stderr)) {
            if (!@fclose($this->stderr)) {
                $this->critical('Errors stream closes is fail.');
            }
        }

        $this->shell = false;
        $this->stderr = false;
    }
}