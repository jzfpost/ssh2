<?php declare(strict_types=1);
/**
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 */

namespace jzfpost\ssh2\Conf;

interface ConfigurationInterface
{
    public function get(string $property): mixed;

    public function set(string $property, mixed $value): self;

    public function getAsArray(): array;

    public function getDefaultProperties(): array;
}