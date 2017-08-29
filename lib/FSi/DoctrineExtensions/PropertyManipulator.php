<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions;

use Closure;
use InvalidArgumentException;
use RuntimeException;

/**
 * @internal
 */
class PropertyManipulator
{
    /**
     * Internal value storage
     *
     * @var array
     */
    private $savedValues = [];

    /**
     * @param object $object
     * @param string $name
     * @param mixed $value
     */
    public function setAndSaveValue($object, $name, $value)
    {
        $this->setPropertyValue($object, $name, $value);
        $this->saveValue($object, $name);
    }

    /**
     * @param object $object
     * @param string $name
     * @return mixed
     */
    public function getPropertyValue($object, $name)
    {
        $this->assertIsObject($object);

        return Closure::bind(function () use ($name) {
            return $this->$name;
        }, $object, $this->getSourceObjectForProperty($object, $name))->__invoke();
    }

    /**
     * @param object $object
     * @param string $name
     * @param mixed $value
     */
    public function setPropertyValue($object, $name, $value)
    {
        $this->assertIsObject($object);

        Closure::bind(function () use ($name, $value) {
            return $this->$name = $value;
        }, $object, $this->getSourceObjectForProperty($object, $name))->__invoke();
    }

    /**
     * @param object $object
     * @param string $name
     * @return boolean
     */
    public function hasSavedValue($object, $name)
    {
        $this->assertIsObject($object);

        $oid = spl_object_hash($object);

        return isset($this->savedValues[$oid])
            && array_key_exists($name, $this->savedValues[$oid])
        ;
    }

    /**
     * @param object $object
     * @param string $name
     * @param boolean $notSavedAsNull
     * @return boolean
     */
    public function hasChangedValue($object, $name, $notSavedAsNull = false)
    {
        $currentValue = $this->getPropertyValue($object, $name);

        if ($notSavedAsNull && !$this->hasSavedValue($object, $name)) {
            return isset($currentValue);
        }

        return $this->getSavedValue($object, $name) !== $currentValue;
    }

    /**
     * @param object $object
     * @param string $name
     * @return mixed
     * @throws BadMethodCallException
     */
    public function getSavedValue($object, $name)
    {
        $this->assertIsObject($object);

        $oid = spl_object_hash($object);
        if (!isset($this->savedValues[$oid])
            || !array_key_exists($name, $this->savedValues[$oid])
        ) {
            throw new RuntimeException(sprintf(
                'Value of property "%s" from specified object was not previously saved',
                $name
            ));
        }

        return $this->savedValues[$oid][$name];
    }

    /**
     * @param object $object
     * @param string $name
     */
    public function saveValue($object, $name)
    {
        $oid = spl_object_hash($object);
        if (!isset($this->savedValues[$oid])) {
            $this->savedValues[$oid] = [];
        }

        $this->savedValues[$oid][$name] = $this->getPropertyValue($object, $name);
    }

    /**
     * @param string $name
     * @return object|string
     */
    private function getSourceObjectForProperty($object, $name)
    {
        $source = $object;
        while (!property_exists($source, $name) && get_parent_class($source) !== false) {
            $source = get_parent_class($source);
        }

        if (!property_exists($source, $name)) {
            throw new RuntimeException(sprintf(
                'Property "%s" does not exist in class "%s" or any of it\'s parents.',
                $name,
                get_class($object)
            ));
        }

        return $source;
    }

    /**
     * @param $object
     * @throws InvalidArgumentException
     */
    private function assertIsObject($object)
    {
        if (is_object($object)) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Expected an object, got "%s"',
            gettype($object)
        ));
    }
}
