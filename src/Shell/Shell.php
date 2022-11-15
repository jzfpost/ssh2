<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */

namespace jzfpost\ssh2\Shell;

use JetBrains\PhpStorm\Pure;
use jzfpost\ssh2\Conf\Configuration;
use jzfpost\ssh2\Exception\Ssh2Exception;
use jzfpost\ssh2\PhpSsh2;
use Psr\Log\LoggerTrait;

final class Shell implements ShellInterface
{
    use LoggerTrait;

    /**
     * RegExp prompts
     */
    public const PROMPT_LINUX = '{username}@[^:]+:~\$';
    public const PROMPT_LINUX_SU = 'root@[^:]+:[^#]+#';
    public const PROMPT_CISCO = '[\w._-]+[#>]';
    public const PROMPT_HUAWEI = '[[<]~?[\w._-]+[]>]';

//    protected string $prompt = '~$';
    /**
     * @var resource|closed-resource|false errors
     */
    protected mixed $errors = false;
    /**
     * @var resource|closed-resource|false shell
     */
    protected mixed $shell = false;

    protected string $buffer = '';
    protected string $history = '';

    private PhpSsh2 $connection;
    private Configuration $configuration;
    /**
     * These are telnet options characters that might be of use for us.
     */
    protected string $_NULL;
    protected string $_DC1;
    protected string $_WILL;
    protected string $_WONT;
    protected string $_DO;
    protected string $_DONT;
    protected string $_IAC;
    protected string $_ESC;

    #[Pure] public function __construct(PhpSsh2 $connection)
    {
        $this->connection = $connection;
        $this->configuration = $this->connection->getConfiguration();

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
    public function open(string $prompt): bool
    {
        if (is_resource($this->shell)) {
            throw new Ssh2Exception("Already opened shell at $this->connection connection");
        }
        if (!$this->connection->isConnected()) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new Ssh2Exception("Failed connecting to host $this->connection");
        }
        if (false === $this->connection->isAuthorised) {
            $this->critical("Failed authorisation on host {host}:{port}");
            throw new Ssh2Exception("Failed authorisation on host $this->connection");
        }

        $this->info('Trying opening shell at {host}:{port} connection');

        if ($this->connection->isConnected()) {
            $this->shell = @ssh2_shell(
                $this->connection->getConnection(),
                $this->configuration->getTermType(),
                $this->configuration->getEnv(),
                $this->configuration->getWidth(),
                $this->configuration->getHeight(),
                $this->configuration->getWidthHeightType()
            );
            if ($this->isOpened()) {

                $this->info('Shell opened success at {host}:{port} connection');

                $this->errors = @ssh2_fetch_stream($this->shell, SSH2_STREAM_STDERR);

                if (false === @stream_set_blocking($this->shell, true)) {
                    $this->critical("Unable to set blocking shell at {host}:{port} connection");
                    throw new Ssh2Exception("Unable to set blocking shell at $this->connection connection");
                }

                $this->readTo($prompt);
                $this->clearBuffer();

                return true;
            }
        }
        $this->critical("Unable to establish shell at {host}:{port} connection");
        throw new Ssh2Exception("Unable to establish shell at $this->connection connection");
    }

    public function isOpened(): bool
    {
        return is_resource($this->shell);
    }

    public function close(): void
    {
        if (is_resource($this->shell)) {
            if (!@fclose($this->shell)) {
                $this->critical('Shell stream closes is fail.');
            }
        }

        if (is_resource($this->errors)) {
            if (!@fclose($this->errors)) {
                $this->critical('Errors stream closes is fail.');
            }
        }

        $this->shell = false;
        $this->errors = false;
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
     * @throws Ssh2Exception
     */
    private function write(string $cmd): void
    {
        $this->clearBuffer();
        $host = $this->connection->host;
        $port = $this->connection->port;

        $context = [
            '{cmd}' => $cmd
        ];

        if (!$this->isOpened()) {
            $this->critical("Unable to establish shell at {host}:{port} connection");
            throw new Ssh2Exception("Unable to establish shell at $host:$port connection");
        }
        $this->info('Write command to host {host}:{port} => "{cmd}"', $context);

        $this->connection->executeTimestamp = microtime(true);

        if ((!fwrite($this->shell, trim($cmd) . PHP_EOL)) < 0) {
            $this->critical("Error writing to shell at {host}:{port} connection");
            throw new Ssh2Exception("Error writing to shell at $host:$port connection");
        }
    }

    /**
     * Reads characters from the shell and adds them to command buffer.
     * Handles telnet control characters. Stops when prompt is encountered.
     *
     * @param string $prompt
     * @return void
     * @throws Ssh2Exception
     */
    private function readTo(string $prompt): void
    {
        $prompt = str_replace('{username}', $this->connection->getUsername(), $prompt);
        $host = $this->connection->host;
        $port = $this->connection->port;

        $this->info('Set prompt to "{prompt}" on shell at {host}:{port} connection', ['{prompt}' => $prompt]);

        if (false === $this->connection->isConnected()) {
            $this->critical("Failed connecting to host {host}:{port}");
            throw new Ssh2Exception("Failed connecting to host $host:$port");
        }

        if (!is_resource($this->shell)) {
            $this->critical("Unable to establish shell at {host}:{port} connection");
            throw new Ssh2Exception("Unable to establish shell at $host:$port connection");
        }

        $this->clearBuffer();

        usleep($this->configuration->getWait());

        $time = time() + $this->configuration->getTimeout();

        do {
            $c = @fgetc($this->shell);
            if (false === $c) {
                $this->info("Couldn't find the requested : '{prompt}', it was not in the data returned from server : '{buffer}'", ['{prompt}' => $prompt, '{buffer}' => $this->buffer]);
                throw new Ssh2Exception("Couldn't find the requested : '$prompt', it was not in the data returned from server : '$this->buffer'");
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
                if (is_float($this->connection->executeTimestamp)) {
                    $this->info("Command execution time is {timestamp} msec", ['{timestamp}' => (string)(microtime(true) - $this->connection->executeTimestamp)]);
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
    public function getErrors(): false|string
    {
        if (is_resource($this->errors)) {
            return fgets($this->errors, 8192);
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
        $this->connection->log($level, $message, $context);
    }

    /**
     * Destructor.
     * @return void
     */
    public function __destruct()
    {
        $this->clearBuffer();
        $this->close();
    }
}