<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf\Methods\TransmittedParams;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

/**
 * @psalm-immutable
 */
final class TransmittedParams implements TransmittedParamsInterface
{
    public function __construct(
        private readonly CryptEnumCollection $crypt = new CryptEnumCollection(CryptEnum::AES256cbc),
        private readonly CompressionEnum $comp = CompressionEnum::none,
        private readonly HmacEnumCollection $mac = new HmacEnumCollection(HmacEnum::sha1),
        private readonly string $lang = ''
    )
    {
    }

    #[Pure]
    #[ArrayShape([
        'crypt' => "string",
        'comp' => "string",
        'mac' => "string",
        'lang' => "string"
    ])]
    public function getAsArray(): array
    {
        return [
            'crypt' => (string) $this->crypt,
            'comp' => $this->comp->getValue(),
            'mac' => (string) $this->mac,
            'lang' => $this->lang
        ];
    }
}