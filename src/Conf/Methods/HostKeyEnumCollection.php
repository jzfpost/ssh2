<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods;

use jzfpost\ssh2\Conf\TypeEnumInterface;

final class HostKeyEnumCollection extends TypeEnumCollection
{
    public function __construct(HostKeyEnum $typeEnum)
    {
        $this->typeEnums[] = $typeEnum;
    }

    public function add(TypeEnumInterface $typeEnum): self
    {
        if ($typeEnum instanceof HostKeyEnum) {
            $this->typeEnums[] = $typeEnum;
        }

        return $this;
    }
}