PHP [SSH2-client](https://github.com/jzfpost/ssh2) connection helper based on ext-ssh2.

Example usage
-------------
```php
$auth = new Password('username', 'password');

$conf = (new Configuration('192.168.1.1'));
        
$ssh2 = new Ssh($conf);
$ssh2->connect()->authentication($auth);

//open shell on network device
$shell = $ssh2->getShell()->open(Shell::PROMPT_CISCO);
$result = $shell->exec('ls -a', Shell::PROMPT_CISCO);

// or exec on linux
$exec = $ssh2->getExec();
$result = $exec->exec('ls -a');

$ssh2->disconnect();
```
