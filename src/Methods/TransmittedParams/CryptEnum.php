<?php

declare(strict_types=1);

namespace jzfpost\ssh2\Methods\TransmittedParams;

use jzfpost\ssh2\Methods\MethodsEnumInterface;

enum CryptEnum: string implements MethodsEnumInterface
{
    case rijndael = 'rijndael-cbc@lysator.liu.se';
    case AES256cbc = 'aes256-cbc';
    case AES256ctr = 'aes256-ctr';
    case AES192cbc = 'aes192-cbc';
    case AES192ctr = 'aes192-ctr';
    case AES128cbc = 'aes128-cbc';
    case AES128ctr = 'aes128-ctr';
    case threeDes = '3des-cbc';
    case blowfish = 'blowfish-cbc';
    case cast128cbc = 'cast128-cbc';
    case arcfour = 'arcfour';
    case arcfour128 = 'arcfour128';
    case none = 'none';
}
