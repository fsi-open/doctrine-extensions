<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml\User;
use Doctrine\ORM\Mapping\Driver\XmlDriver;

class GeneralXmlTest extends GeneralTest
{
    const USER = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Xml\\User';
    const BASE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Xml\\';

    /**
     * @dataProvider wrongClasses
     */
    public function testWrongAnnotations($class)
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, $class);
    }

    public static function wrongClasses()
    {
        $classes = array();
        for ($i = 1; $i < 8; $i++) {
            $classes[] = array(self::BASE . 'User' . $i);
        }
        return $classes;
    }

    /**
     * {@inehritDoc}
     */
    protected function getMetadataDriverImplementation()
    {
        return new XmlDriver(__DIR__.'/Fixture/Xml/config');
    }

    /**
     * {@inheritDoc}
     *
     * @return FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml\User
     */
    protected function getUser()
    {
        return new User();
    }

    /**
     * {@inheritDoc}
     */
    protected function getUsedEntityFixtures()
    {
        return array(
            self::USER,
        );
    }
}
