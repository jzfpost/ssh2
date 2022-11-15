<?php declare(strict_types=1);
/**
 * @author jzfpost@gmail.com
 */
namespace jzfpost\ssh2;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param non-empty-string $name
     * @param object $class
     * @return ReflectionProperty
     * @throws ReflectionException
     */
    protected static function getUnaccessibleProperty(string $name, object $class): ReflectionProperty
    {
        $new = new ReflectionClass(clone $class);

        return $new->getProperty($name);
    }

    /**
     * @param non-empty-string $name
     * @param object $class
     * @return mixed
     * @throws ReflectionException
     */
    protected static function getUnaccessiblePropertyValue(string $name, object $class): mixed
    {
        $property = self::getUnaccessibleProperty($name, $class);

        return $property->getValue(clone $class);
    }

    /**
     * @param non-empty-string $name
     * @param object $class
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getUnaccessibleMethod(string $name, object $class): ReflectionMethod
    {
        $new = new ReflectionClass(clone $class);

        return $new->getMethod($name);
    }

    /**
     * @param non-empty-string $name
     * @param object $class
     * @param array $args //Method arguments in array
     * @return mixed
     * @throws ReflectionException
     */
    protected static function getUnaccessibleMethodReturn(string $name, object $class, array $args): mixed
    {
        $method = self::getUnaccessibleMethod($name, $class);

        return $method->invokeArgs(clone $class, $args);
    }
}