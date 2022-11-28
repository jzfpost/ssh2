<?php declare(strict_types=1);

namespace jzfpost\ssh2\Exec;

enum PromptEnum: string
{
    case linux = '[^@]@[^:]+:~\$';
    case cisco = '^[\w._-]+[#>]';
    case huawei = '[[<]~?[\w._-]+[]>]';
}
