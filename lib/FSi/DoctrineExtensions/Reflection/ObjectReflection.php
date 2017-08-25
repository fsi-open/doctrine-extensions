<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Reflection;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * @internal
 */
class ObjectReflection
{
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
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getPropertyValue($name)
    {
        return Closure::bind(function () use ($name) {
            return $this->$name;
        }, $this->object, $this->getSourceObjectForProperty($name))->__invoke();
    }


    /**
     * @param string $name
     * @param mixed $value
     */
    public function setPropertyValue($name, $value)
    {
        return Closure::bind(function () use ($name, $value) {
            return $this->$name = $value;
        }, $this->object, $this->getSourceObjectForProperty($name))->__invoke();
    }

    /**
     * @param string $name
     * @return object|string
     */
    public function getSourceObjectForProperty($name)
    {
        $source = $this->object;
        while (!property_exists($source, $name) && get_parent_class($source) !== false) {
            $source = get_parent_class($source);
        }

        if (!property_exists($source, $name)) {
            throw new RuntimeException(sprintf(
                'Property "%s" does not exist in class "%s" or any of it\'s parents.',
                $name,
                get_class($this->object)
            ));
        }

        return $source;
    }

    public function getObject()
    {
        return $this->object;
    }
}
