<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods;

use jzfpost\ssh2\Conf\TypeEnumInterface;
use Stringable;

interface TypeEnumCollectionInterface extends Stringable
{
    public function __toString(): string;

    public function add(TypeEnumInterface $typeEnum): self;

    public function clear(): self;
}