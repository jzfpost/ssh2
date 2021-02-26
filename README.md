PHP [SSH2-client](https://github.com/jzfpost/ssh2) connection helper.

Example usage
-------------
```php
$ssh2 = new PhpSsh2(['timeout' => 10, 'wait' => '3500', 'logging' => '/var/log/ssh2/log.txt', 'screenLogging' => true]);
$ssh2->connect('localhost')
    ->authPassword('username', 'password')
    ->openShell(PhpSsh2::PROMPT_LINUX, 'xterm');
$result = $ssh2->send('ls -a', PhpSsh2::PROMPT_LINUX);
$ssh2->disconnect();
```
