<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests;

use FSi\DoctrineExtensions\PropertyManipulator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PropertyManipulatorTest extends TestCase
{
    public function testValueChanged(): void
    {
        $this->expectException(RuntimeException::class);

        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $observer->saveValue($object, 'property1');
        $observer->saveValue($object, 'property2');
        $observer->saveValue($object, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        self::assertTrue($observer->hasSavedValue($object, 'property1'));
        self::assertTrue($observer->hasChangedValue($object, 'property1'));
        self::assertTrue($observer->hasSavedValue($object, 'property2'));
        self::assertFalse($observer->hasChangedValue($object, 'property2'));
        self::assertTrue($observer->hasChangedValue($object, 'property3'));
        self::assertTrue($observer->hasSavedValue($object, 'property3'));
        self::assertFalse($observer->hasSavedValue($object, 'property4'));
        $observer->hasChangedValue($object, 'property4');
    }

    public function testChangedValue(): void
    {
        $this->expectException(RuntimeException::class);

        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $observer->saveValue($object, 'property1');
        $observer->saveValue($object, 'property2');
        $observer->saveValue($object, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        self::assertTrue($observer->hasSavedValue($object, 'property1'));
        self::assertTrue($observer->hasChangedValue($object, 'property1'));
        self::assertTrue($observer->hasSavedValue($object, 'property2'));
        self::assertFalse($observer->hasChangedValue($object, 'property2'));
        self::assertTrue($observer->hasChangedValue($object, 'property3'));
        self::assertTrue($observer->hasSavedValue($object, 'property3'));
        self::assertFalse($observer->hasSavedValue($object, 'property4'));
        $observer->hasChangedValue($object, 'property4');
    }

    public function testSetValue(): void
    {
        $this->expectException(RuntimeException::class);

        $observer = new PropertyManipulator();

        $object = new TestObject();
        $observer->setAndSaveValue($object, 'property1', 'original value 1');
        $observer->setAndSaveValue($object, 'property2', 'original value 2');
        $observer->setAndSaveValue($object, 'property3', 'original value 3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        self::assertTrue($observer->hasChangedValue($object, 'property1'));
        self::assertFalse($observer->hasChangedValue($object, 'property2'));
        self::assertTrue($observer->hasChangedValue($object, 'property3'));
        $observer->hasChangedValue($object, 'property4');
    }

    public function testGetSavedValue(): void
    {
        $this->expectException(RuntimeException::class);

        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';

        $observer->saveValue($object, 'property1');
        $observer->saveValue($object, 'property2');
        $observer->saveValue($object, 'property3');

        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';
        self::assertEquals(
            $observer->getSavedValue($object, 'property1'),
            'original value 1'
        );
        self::assertEquals(
            $observer->getSavedValue($object, 'property2'),
            'original value 2'
        );
        self::assertNull($observer->getSavedValue($object, 'property3'));
        $observer->getSavedValue($object, 'property4');
    }

    public function testTreatNotSavedAsNull(): void
    {
        $observer = new PropertyManipulator();

        $object = new TestObject();
        $object->property1 = 'original value 1';
        $object->property2 = 'original value 2';
        $object->property1 = 'new value 1';
        $object->property3 = 'new value 3';

        self::assertTrue($observer->hasChangedValue($object, 'property1', true));
        self::assertTrue($observer->hasChangedValue($object, 'property2', true));
        self::assertTrue($observer->hasChangedValue($object, 'property3', true));
        self::assertFalse($observer->hasChangedValue($object, 'property4', true));
    }
}
