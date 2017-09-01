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
     * @param string $property
     * @param mixed $value
     */
    public function setAndSaveValue($object, $property, $value)
    {
        $this->setPropertyValue($object, $property, $value);
        $this->saveValue($object, $property);
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed
     */
    public function getPropertyValue($object, $property)
    {
        $this->assertIsObject($object);

        return Closure::bind(function () use ($property) {
            return $this->$property;
        }, $object, $this->getSourceObjectForProperty($object, $property))->__invoke();
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    public function setPropertyValue($object, $property, $value)
    {
        $this->assertIsObject($object);

        Closure::bind(function () use ($property, $value) {
            return $this->$property = $value;
        }, $object, $this->getSourceObjectForProperty($object, $property))->__invoke();
    }

    /**
     * @param object $object
     * @param string $property
     * @return boolean
     */
    public function hasSavedValue($object, $property)
    {
        $this->assertIsObject($object);

        $oid = spl_object_hash($object);

        return isset($this->savedValues[$oid])
            && array_key_exists($property, $this->savedValues[$oid])
        ;
    }

    /**
     * @param object $object
     * @param string $property
     * @param boolean $notSavedAsNull
     * @return boolean
     */
    public function hasChangedValue($object, $property, $notSavedAsNull = false)
    {
        $currentValue = $this->getPropertyValue($object, $property);

        if ($notSavedAsNull && !$this->hasSavedValue($object, $property)) {
            return isset($currentValue);
        }

        return $this->getSavedValue($object, $property) !== $currentValue;
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed
     * @throws BadMethodCallException
     */
    public function getSavedValue($object, $property)
    {
        $this->assertIsObject($object);

        $oid = spl_object_hash($object);
        if (!isset($this->savedValues[$oid]) || !array_key_exists($property, $this->savedValues[$oid])) {
            throw new RuntimeException(sprintf(
                'Value of property "%s" from specified object was not previously saved',
                $property
            ));
        }

        return $this->savedValues[$oid][$property];
    }

    /**
     * @param object $object
     * @param string $property
     */
    public function saveValue($object, $property)
    {
        $oid = spl_object_hash($object);
        if (!isset($this->savedValues[$oid])) {
            $this->savedValues[$oid] = [];
        }

        $this->savedValues[$oid][$property] = $this->getPropertyValue($object, $property);
    }

    /**
     * @param string $property
     * @return object|string
     */
    private function getSourceObjectForProperty($object, $property)
    {
        $source = $object;
        while (!property_exists($source, $property) && get_parent_class($source) !== false) {
            $source = get_parent_class($source);
        }

        if (!property_exists($source, $property)) {
            throw new RuntimeException(sprintf(
                'Property "%s" does not exist in class "%s" or any of it\'s parents.',
                $property,
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
