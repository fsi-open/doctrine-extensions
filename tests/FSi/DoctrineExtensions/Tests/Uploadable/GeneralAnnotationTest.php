<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\User;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;
use TypeError;

class GeneralAnnotationTest extends GeneralTest
{
    public const BASE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Annotation\\';

    /**
     * @dataProvider wrongAnnotations()
     */
    public function testWrongAnnotations(string $class)
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

    public function wrongAnnotations()
    {
        return [
            [sprintf('%sUser2', self::BASE)],
            [sprintf('%sUser3', self::BASE)],
            [sprintf('%sUser4', self::BASE)],
            [sprintf('%sUser6', self::BASE)],
            [sprintf('%sUser7', self::BASE)],
        ];
    }

    public function wrongTypes()
    {
        return [
            [sprintf('%sUser1', self::BASE)],
            [sprintf('%sUser8', self::BASE)],
            [sprintf('%sUser9', self::BASE)],
            [sprintf('%sUser10', self::BASE)],
            [sprintf('%sUser11', self::BASE)],
        ];
    }

    protected function getUser(): User
    {
        return new User();
    }

    protected function getUsedEntityFixtures()
    {
        return [User::class];
    }
}
