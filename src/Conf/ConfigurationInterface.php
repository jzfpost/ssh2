<?php

declare(strict_types=1);
/**
 * @package     jzfpost\ssh2
 *
 * @category    Net
 * @author      Eugenith <jzfpost@gmail.com>
 * @copyright   jzfpost
 * @license     see LICENSE.txt
 * @link        https://github/jzfpost/ssh2
 * @requires    ext-ssh2 version => ^1.3.1
 * @requires    libssh2 version => ^1.8.0
 */

namespace jzfpost\ssh2\Conf;

interface ConfigurationInterface
{
    public function get(string $property): mixed;

    public function set(string $property, mixed $value): self;

    public function getAsArray(): array;

    public function getDefaultProperties(): array;

    /**
     * @param array<string, mixed> $options
     */
    public function setFromArray(array $options = []): self;
}