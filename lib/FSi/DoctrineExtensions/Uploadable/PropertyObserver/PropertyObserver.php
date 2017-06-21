<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\PropertyObserver;

use FSi\DoctrineExtensions\Uploadable\PropertyObserver\Exception\BadMethodCallException;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PropertyObserver
{
    /**
     * Internal value storage
     *
     * @var array
     */
    private $savedValues = [];

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param object $object
     * @param string $propertyPath
     * @param mixed $value
     */
    public function setValue($object, $propertyPath, $value)
    {
        $this->validateObject($object);
        $this->propertyAccessor->setValue($object, $propertyPath, $value);
        $this->saveValue($object, $propertyPath);
    }

    /**
     * @param object $object
     * @param string $propertyPath
     */
    public function saveValue($object, $propertyPath)
    {
        $this->validateObject($object);
        $oid = spl_object_hash($object);
        if (!isset($this->savedValues[$oid])) {
            $this->savedValues[$oid] = [];
        }
        $this->savedValues[$oid][$propertyPath] = $this->propertyAccessor->getValue($object, $propertyPath);
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
     * @param object $object
     * @param string $propertyPath
     */
    public function resetValue($object, $propertyPath)
    {
        $this->propertyAccessor->setValue(
            $object,
            $propertyPath,
            $this->getSavedValue($object, $propertyPath)
        );
    }

    /**
     * @param object $object
     * @param string $propertyPath
     * @param boolean $notSavedAsNull
     * @return boolean
     */
    public function hasChangedValue($object, $propertyPath, $notSavedAsNull = false)
    {
        $this->validateObject($object);

        $currentValue = $this->propertyAccessor->getValue($object, $propertyPath);

        if ($notSavedAsNull && !$this->hasSavedValue($object, $propertyPath)) {
            return isset($currentValue);
        }

        return $this->getSavedValue($object, $propertyPath) !== $currentValue;
    }

    /**
     * @param object $object
     * @param string $propertyPath
     * @return boolean
     */
    public function hasValueChanged($object, $propertyPath)
    {
        return $this->hasChangedValue($object, $propertyPath);
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
