<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use Doctrine\ORM\Mapping\Driver\XmlDriver;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml\Car;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;
use TypeError;

class GeneralXmlTest extends GeneralTest
{
    public const BASE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Common\\';

    /**
     * @dataProvider wrongMappings
     */
    public function testWrongMapping($class)
    {
        $this->expectException(MappingException::class);
        $this->_uploadableListener->getExtendedMetadata($this->_em, $class);
    }

    /**
     * @dataProvider wrongTypes()
     */
    public function testWrongTypes(string $class)
    {
        $this->expectException(TypeError::class);
        $this->_uploadableListener->getExtendedMetadata($this->_em, $class);
    }

    public function wrongMappings()
    {
        return [
            [sprintf('%sUser1', self::BASE)],
            [sprintf('%sUser2', self::BASE)],
            [sprintf('%sUser3', self::BASE)],
            [sprintf('%sUser4', self::BASE)],
            [sprintf('%sUser7', self::BASE)],
        ];
    }

    public function wrongTypes()
    {
        return [
            [sprintf('%sUser6', self::BASE)],
            [sprintf('%sUser8', self::BASE)],
        ];
    }

    public function testMappingWithOtherNamespaces()
    {
        $this->_uploadableListener->getExtendedMetadata($this->_em, Car::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMetadataDriverImplementation()
    {
        return new XmlDriver(__DIR__.'/Fixture/Xml/config');
    }

    /**
     * {@inheritdoc}
     *
     * @return User
     */
    protected function getUser()
    {
        return new User();
    }

    /**
     * {@inheritdoc}
     */
    protected function getUsedEntityFixtures()
    {
        return [User::class];
    }
}
