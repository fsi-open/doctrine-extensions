<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User1;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User2;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User3;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User4;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User6;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User7;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User8;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User9;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation\User10;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;
use TypeError;

class GeneralAnnotationTest extends GeneralTest
{
    /**
     * @dataProvider wrongAnnotations()
     */
    public function testWrongAnnotations(string $class): void
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

    public function wrongAnnotations(): array
    {
        return [
            [User2::class],
            [User3::class],
            [User4::class],
            [User6::class],
            [User7::class],
        ];
    }

    public function wrongTypes(): array
    {
        return [
            [User1::class],
            [User8::class],
            [User9::class],
            [User10::class],
        ];
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
