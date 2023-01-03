PHP [SSH2-client](https://github.com/jzfpost/ssh2) connection helper based on ext-ssh2.

Example usage
-------------

```php
$auth = new Password('username', 'password');

$conf = (new Configuration())
    ->setTermType(TermTypeEnum::dumb);
        
$ssh2 = new Ssh($conf, new RealtimeLogger());
$ssh2 = $ssh2->connect('192.168.1.1')
    ->authPassword('jzf', 'Ob$curite_25');
    
if (!$ssh->authenticate()) {
    throw new RuntimeException('Not authorised');
};

//open shell on network device
$shell = $ssh2->getShell()->open(PromptEnum::cisco->value);
$result = $shell->exec('show version');

// or exec on linux
$exec = $ssh2->getExec();
$result = $exec->exec('ls -a');

$ssh2->disconnect();
```
