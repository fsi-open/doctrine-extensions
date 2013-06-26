<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;

class KeymakerTest extends \PHPUnit_Framework_TestCase
{
    const ID = 1;

    protected $keyLength = 255;

    protected $keymaker;

    public function setUp()
    {
        $this->keymaker = new Entity();
    }

    public function provider()
    {
        return array(
            array(255),
            array(100),
            array(73),
            array(60),
            array(30),
            array(26),
        );
    }

    public function provider2()
    {
        return array(
            array(255),
            array(100),
            array(73),
            array(60),
            array(30),
            array(22),
        );
    }

    /**
     * @dataProvider provider
     */
    public function testKeymaker($keyLength)
    {
        $object = new User();
        // This will generate name with 42 characters, so after name shortening (of original name) it should generate key (with one character id) with minimum 26 characters.
        $this->assertLessThanOrEqual($keyLength, mb_strlen($this->keymaker->createKey($object, 'someProperty', self::ID, 'originalFilename.jpg2', $keyLength)));
    }

    /**
     * Test with original name without extension.
     *
     * @dataProvider provider2
     */
    public function testKeymakerWithoutExtension($keyLength)
    {
        $object = new User();
        // This will generate name with 37 characters, so after name shortening (of original name) it should generate key (with one character id) with minimum 22 characters.
        $this->assertLessThanOrEqual($keyLength, mb_strlen($this->keymaker->createKey($object, 'someProperty', self::ID, 'originalFilename', $keyLength)));
    }

    public function testKeymakerWithoutEnoughSpace()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $object = new User();
        $this->keymaker->createKey($object, 'someProperty', self::ID, 'originalFilename.jpg', 25);
    }

    public function testKeymakerWithZeroKeyLength()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $object = new User();
        $this->keymaker->createKey($object, 'someProperty', self::ID, 'originalName.jpg', 0);
    }

    public function testKeymakerWithNegativeKeyLength()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $object = new User();
        $this->keymaker->createKey($object, 'someProperty', self::ID, 'originalName.jpg', -1);
    }
}
