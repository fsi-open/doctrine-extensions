<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Reflection;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

/**
 * @internal
 */
class ObjectReflection
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @var object
     */
    private $object;

    /**
     * @param object $object
     */
    public function __construct($object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(sprintf(
                'Can only reflect objects, got "%s"',
                gettype($object)
            ));
        }

        $this->object = $object;
        $this->reflection = new ReflectionClass($object);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getPropertyValue($name)
    {
        $property = $this->getProperty($name);
        $property->setAccessible(true);
        $value = $property->getValue($this->object);
        $property->setAccessible(false);

        return $value;
    }


    /**
     * @param string $name
     * @param mixed $value
     */
    public function setPropertyValue($name, $value)
    {
        $property = $this->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this->object, $value);
        $property->setAccessible(false);
    }

    /**
     * @param string $name
     * @return ReflectionProperty
     */
    public function getProperty($name)
    {
        $reflection = $this->reflection;
        while (!($property = $this->attemptToExtractProperty($reflection, $name))) {
            $reflection = $reflection->getParentClass();
        }

        if (!$property) {
            throw new RuntimeException(sprintf(
                'Property "%s" does not exist in class "%s" or any of it\'s parents.',
                $name,
                get_class($this->object)
            ));
        }

        return $property;
    }

    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param ReflectionClass $reflection
     * @param string $name
     * @return ReflectionProperty|bool
     * @throws RuntimeException
     */
    private function attemptToExtractProperty(ReflectionClass $reflection, $name)
    {
        if ($reflection->hasProperty($name)) {
            return $reflection->getProperty($name);
        }

        /* @var $traitReflection ReflectionClass */
        foreach ($reflection->getTraits() as $traitReflection) {
            if ($traitReflection->hasProperty($name)) {
                return $traitReflection->getProperty($name);
            }
        }

        return false;
    }
}
