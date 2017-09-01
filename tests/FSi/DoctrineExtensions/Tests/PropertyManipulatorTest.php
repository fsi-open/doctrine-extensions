<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\PropertyManipulator;

use FSi\DoctrineExtensions\Tests\Uploadable\PropertyManipulator\TestObject;
use FSi\DoctrineExtensions\PropertyManipulator;
use PHPUnit_Framework_TestCase;

class PropertyManipulatorTest extends PHPUnit_Framework_TestCase
{
    public function testValueChanged()
    {
        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $observer->saveValue($object, 'property1');
        $observer->saveValue($object, 'property2');
        $observer->saveValue($object, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertTrue($observer->hasSavedValue($object, 'property1'));
        $this->assertTrue($observer->hasChangedValue($object, 'property1'));
        $this->assertTrue($observer->hasSavedValue($object, 'property2'));
        $this->assertFalse($observer->hasChangedValue($object, 'property2'));
        $this->assertTrue($observer->hasChangedValue($object, 'property3'));
        $this->assertTrue($observer->hasSavedValue($object, 'property3'));
        $this->assertFalse($observer->hasSavedValue($object, 'property4'));
        $this->setExpectedException('\RuntimeException');
        $observer->hasChangedValue($object, 'property4');
    }

    public function testChangedValue()
    {
        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $observer->saveValue($object, 'property1');
        $observer->saveValue($object, 'property2');
        $observer->saveValue($object, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertTrue($observer->hasSavedValue($object, 'property1'));
        $this->assertTrue($observer->hasChangedValue($object, 'property1'));
        $this->assertTrue($observer->hasSavedValue($object, 'property2'));
        $this->assertFalse($observer->hasChangedValue($object, 'property2'));
        $this->assertTrue($observer->hasChangedValue($object, 'property3'));
        $this->assertTrue($observer->hasSavedValue($object, 'property3'));
        $this->assertFalse($observer->hasSavedValue($object, 'property4'));
        $this->setExpectedException('\RuntimeException');
        $observer->hasChangedValue($object, 'property4');
    }

    public function testSetValue()
    {
        $observer = new PropertyManipulator();

        $object = new TestObject();
        $observer->setAndSaveValue($object, 'property1', 'original value 1');
        $observer->setAndSaveValue($object, 'property2', 'original value 2');
        $observer->setAndSaveValue($object, 'property3', 'original value 3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertTrue($observer->hasChangedValue($object, 'property1'));
        $this->assertFalse($observer->hasChangedValue($object, 'property2'));
        $this->assertTrue($observer->hasChangedValue($object, 'property3'));
        $this->setExpectedException('\RuntimeException');
        $observer->hasChangedValue($object, 'property4');
    }

    public function testGetSavedValue()
    {
        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $observer->saveValue($object, 'property1');
        $observer->saveValue($object, 'property2');
        $observer->saveValue($object, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        $this->assertEquals(
            $observer->getSavedValue($object, 'property1'),
            'original value 1'
        );
        $this->assertEquals(
            $observer->getSavedValue($object, 'property2'),
            'original value 2'
        );
        $this->assertNull($observer->getSavedValue($object, 'property3'));
        $this->setExpectedException('\RuntimeException');
        $observer->getSavedValue($object, 'property4');
    }

    public function testTreatNotSavedAsNull()
    {
        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';
        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';

        $this->assertTrue($observer->hasChangedValue($object, 'property1', true));
        $this->assertTrue($observer->hasChangedValue($object, 'property2', true));
        $this->assertTrue($observer->hasChangedValue($object, 'property3', true));
        $this->assertFalse($observer->hasChangedValue($object, 'property4', true));
    }
}

class TestObject
{
    public $property1;

    public $property2;

    public $property3;

    public $property4;
}
