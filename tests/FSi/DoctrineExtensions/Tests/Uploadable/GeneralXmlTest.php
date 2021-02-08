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
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Common\User1;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Common\User2;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Common\User3;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Common\User4;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Common\User6;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Common\User7;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml\Car;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;
use TypeError;

class GeneralXmlTest extends GeneralTest
{
    /**
     * @dataProvider wrongMappings
     */
    public function testWrongMapping($class): void
    {
        $this->expectException(MappingException::class);
        $this->uploadableListener->getExtendedMetadata($this->entityManager, $class);
    }

    /**
     * @dataProvider wrongTypes()
     */
    public function testWrongTypes(string $class): void
    {
        $this->expectException(TypeError::class);
        $this->uploadableListener->getExtendedMetadata($this->entityManager, $class);
    }

    public function wrongMappings(): array
    {
        return [
            [User1::class],
            [User2::class],
            [User3::class],
            [User4::class],
            [User7::class],
        ];
    }

    public function wrongTypes(): array
    {
        return [
            [User6::class],
        ];
    }

    public function testMappingWithOtherNamespaces(): void
    {
        $this->uploadableListener->getExtendedMetadata($this->entityManager, Car::class);
    }

    protected function getMetadataDriverImplementation(): MappingDriver
    {
        return new XmlDriver(__DIR__.'/Fixture/Xml/config');
    }

    protected function getUser(): User
    {
        return new User();
    }

    protected function getUsedEntityFixtures(): array
    {
        return [User::class];
    }
}
