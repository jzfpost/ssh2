<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods\TransmittedParams;

use jzfpost\ssh2\Conf\Methods\TypeEnumCollection;
use jzfpost\ssh2\Conf\TypeEnumInterface;

final class HmacEnumCollection extends TypeEnumCollection
{
    public function __construct(HmacEnum $typeEnum)
    {
        $this->typeEnums[] = $typeEnum;
    }

    public function add(TypeEnumInterface $typeEnum): self
    {
        if ($typeEnum instanceof HmacEnum) {
            $this->typeEnums[] = $typeEnum;
        }

        return $this;
    }
}