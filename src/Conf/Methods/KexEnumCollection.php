<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods;

use jzfpost\ssh2\Conf\TypeEnumInterface;

final class KexEnumCollection extends TypeEnumCollection
{
    public function __construct(KexEnum $typeEnum)
    {
        $this->typeEnums[] = $typeEnum;
    }

    public function add(TypeEnumInterface $typeEnum): self
    {
        if ($typeEnum instanceof KexEnum) {
            $this->typeEnums[] = $typeEnum;
        }

        return $this;
    }
}