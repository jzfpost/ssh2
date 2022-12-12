<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods;

use jzfpost\ssh2\Conf\TypeEnumInterface;

abstract class TypeEnumCollection implements TypeEnumCollectionInterface
{
    /**
     * @var TypeEnumInterface[]
     */
    protected array $typeEnums = [];

    abstract public function add(TypeEnumInterface $typeEnum): self;

    public function clear(): self
    {
        $new = clone $this;
        $new->typeEnums = [];

        return $new;
    }

    public function __toString(): string
    {
        $values = [];
        foreach ($this->typeEnums as $typeEnum) {
            $values[] = $typeEnum->getValue();
        }

        return implode(', ', array_unique($values));
    }
}