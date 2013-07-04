<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;
use FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;

class KeymakerTest extends \PHPUnit_Framework_TestCase
{
    const PROPERTY = 'property';
    const ID = 1;
    const ORIGINAL_NAME = 'originalName.txt';

    public function testCreation()
    {
        $keyMaker = new Entity();
        $this->assertTrue($keyMaker instanceof KeymakerInterface);
    }

    /**
     * @dataProvider inputs
     */
    public function testKeyGeneration($pattern, $expected)
    {
        $keyMaker = new Entity();
        $user = new User();

        $this->assertEquals($expected, $keyMaker->createKey($user, self::PROPERTY, self::ID, self::ORIGINAL_NAME, $pattern));
    }

    public static function inputs()
    {
        return array(
            array(null, '/FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::PROPERTY . '/' . self::ID . '/' . self::ORIGINAL_NAME),
            array('{fqcn}/{id}/constant', 'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::ID . '/constant'),
            array('{fqcn}/{property}/{wrong_tag}/{id}/{original_name}', 'FSiDoctrineExtensionsTestsUploadableFixtureUser/' . self::PROPERTY . '/{wrong_tag}/' . self::ID . '/' . self::ORIGINAL_NAME),
        );
    }
}
