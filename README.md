PHP [SSH2-client](https://github.com/jzfpost/ssh2) connection helper.

Example usage
-------------
```php
$auth = new Password('username', 'password');

$conf = (new Configuration('192.168.1.1'))
        ->setLoggingFileName("/var/log/ssh2/log.txt")
        ->setDebugMode();
        
$ssh2 = new PhpSsh2($conf);
$ssh2->connect()->auth($auth);

$shell = $ssh2->getShell()
    ->open(PhpSsh2::PROMPT_LINUX);

$result = $shell->send('ls -a', PhpSsh2::PROMPT_LINUX);

$ssh2->disconnect();
```
