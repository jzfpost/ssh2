<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2;

use jzfpost\ssh2\Conf\Configuration;

interface Configurable
{
    public function getConfiguration(): Configuration;
}