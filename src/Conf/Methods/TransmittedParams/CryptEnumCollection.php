<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods\TransmittedParams;

use jzfpost\ssh2\Conf\Methods\TypeEnumCollection;
use jzfpost\ssh2\Conf\TypeEnumInterface;

final class CryptEnumCollection extends TypeEnumCollection
{
    public function __construct(CryptEnum $typeEnum)
    {
        $this->typeEnums[] = $typeEnum;
    }

    public function add(TypeEnumInterface $typeEnum): self
    {
        if ($typeEnum instanceof CryptEnum) {
            $this->typeEnums[] = $typeEnum;
        }

        return $this;
    }
}