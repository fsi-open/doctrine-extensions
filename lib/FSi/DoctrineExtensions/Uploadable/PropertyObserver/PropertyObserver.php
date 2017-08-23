<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\PropertyObserver;

use FSi\DoctrineExtensions\Reflection\ObjectReflection;
use FSi\DoctrineExtensions\Uploadable\PropertyObserver\Exception\BadMethodCallException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

class PropertyObserver
{
    /**
     * Internal value storage
     *
     * @var array
     */
    private $savedValues = [];

    /**
     * @param ObjectReflection $reflection
     * @param string $propertyPath
     * @param mixed $value
     */
    public function setValue(ObjectReflection $reflection, $propertyPath, $value)
    {
        $reflection->setPropertyValue($propertyPath, $value);
        $this->saveValue($reflection, $propertyPath);
    }

    /**
     * @param ObjectReflection $reflection
     * @param string $propertyPath
     */
    public function saveValue(ObjectReflection $reflection, $propertyPath)
    {
        $oid = spl_object_hash($reflection->getObject());
        if (!isset($this->savedValues[$oid])) {
            $this->savedValues[$oid] = [];
        }
        $this->savedValues[$oid][$propertyPath] = $reflection->getPropertyValue($propertyPath);
    }

    /**
     * @param object $object
     * @param string $propertyPath
     * @return boolean
     */
    public function hasSavedValue($object, $propertyPath)
    {
        $this->validateObject($object);
        $oid = spl_object_hash($object);
        return isset($this->savedValues[$oid]) && array_key_exists($propertyPath, $this->savedValues[$oid]);
    }

    /**
     * @param object $object
     * @param string $propertyPath
     * @return mixed
     * @throws BadMethodCallException
     */
    public function getSavedValue($object, $propertyPath)
    {
        $this->validateObject($object);

        $oid = spl_object_hash($object);
        if (!isset($this->savedValues[$oid])
            || !array_key_exists($propertyPath, $this->savedValues[$oid])
        ) {
            throw new BadMethodCallException(sprintf(
                'Value of property "%s" from specified object was not saved previously',
                $propertyPath
            ));
        }

        return $this->savedValues[$oid][$propertyPath];
    }

    /**
     * @param ObjectReflection $reflection
     * @param string $propertyPath
     */
    public function resetValue(ObjectReflection $reflection, $propertyPath)
    {
        $reflection->setPropertyValue(
            $propertyPath,
            $this->getSavedValue($reflection->getObject(), $propertyPath)
        );
    }

    /**
     * @param ObjectReflection $reflection
     * @param string $propertyPath
     * @param boolean $notSavedAsNull
     * @return boolean
     */
    public function hasChangedValue(ObjectReflection $reflection, $propertyPath, $notSavedAsNull = false)
    {
        $object = $reflection->getObject();
        $currentValue = $reflection->getPropertyValue($propertyPath);

        if ($notSavedAsNull && !$this->hasSavedValue($object, $propertyPath)) {
            return isset($currentValue);
        }

        return $this->getSavedValue($object, $propertyPath) !== $currentValue;
    }

    /**
     * @param object $object
     */
    public function remove($object)
    {
        $this->validateObject($object);

        $oid = spl_object_hash($object);
        unset($this->savedValues[$oid]);
    }

    public function clear()
    {
        $this->savedValues = [];
    }

    /**
     * @param $object
     * @throws InvalidArgumentException
     */
    protected function validateObject($object)
    {
        if (!is_object($object)) {
            throw new InvalidArgumentException(
                'Only object\'s properties could be observed by PropertyObserver'
            );
        }
    }
}
