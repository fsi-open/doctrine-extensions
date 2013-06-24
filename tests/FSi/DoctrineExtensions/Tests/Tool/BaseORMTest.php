<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Tool;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Common\Util\Debug;
use FSi\DoctrineExtensions\Translatable\TranslatableListener;
use FSi\DoctrineExtensions\Uploadable\UploadableListener;
use FSi\DoctrineExtensions\Versionable\VersionableListener;
use FSi\DoctrineExtensions\LoStorage\LoStorageListener;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;

/**
 * This is the base test class for other Doctrine related tests
 *
 * @author Lukasz Cybula <lukasz@fsi.pl>
 */
abstract class BaseORMTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * @var \FSi\DoctrineExtensions\Translatable\TranslatableListener
     */
    protected $_translatableListener;

    /**
     * @var \FSi\DoctrineExtensions\Versionable\VersionableListener
     */
    protected $_versionableListener;

    /**
     * @var \FSi\DoctrineExtensions\LoStorage\LoStorageListener
     */
    protected $_loListener;

    /**
     * @var \FSi\DoctrineExtensions\Uploadable\UploadableListener
     */
    protected $_uploadableListener;


    /**
     * @var \Gaufrette\Filesystem
     */
    protected $_filesystem1;

    /**
     * @var \Gaufrette\Filesystem
     */
    protected $_filesystem2;

    /**
     * @var \Doctrine\DBAL\Logging\DebugStack
     */
    protected $_logger;

    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getMetadataDriverImplementation()
    {
        return new AnnotationDriver($_ENV['annotation_reader'], __DIR__.'/../LoStorage/Fixture');
    }

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->getEntityManager();
    }

    protected function getMockAnnotatedConfig()
    {
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config
            ->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(TESTS_TEMP_DIR))
            ;

        $config
            ->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'))
            ;

        $config
            ->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true))
            ;

        $config
            ->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'))
            ;

        $config
            ->expects($this->any())
            ->method('getQuoteStrategy')
            ->will($this->returnValue(new \Doctrine\ORM\Mapping\DefaultQuoteStrategy()))
            ;

        $config
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->will($this->returnCallback(function ($hydrationMode) {
                if ($hydrationMode == \FSi\DoctrineExtensions\ORM\Query::HYDRATE_OBJECT) {
                    return 'FSi\DoctrineExtensions\ORM\Hydration\ObjectHydrator';
                }
            }))
            ;

        $mappingDriver = $this->getMetadataDriverImplementation();

        $config
            ->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver))
        ;

        $config
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'))
            ;

        $this->_logger = new \Doctrine\DBAL\Logging\DebugStack();
        $this->_logger->enabled = false;

        $config
            ->expects($this->any())
            ->method('getSQLLogger')
            ->will($this->returnValue($this->_logger));

        return $config;
    }

    protected function getEntityManager()
    {
        $evm = new EventManager;
        $this->_translatableListener = new TranslatableListener();
        $evm->addEventSubscriber($this->_translatableListener);
        $this->_versionableListener = new VersionableListener();
        $evm->addEventSubscriber($this->_versionableListener);
        $this->_loListener = new LoStorageListener(array('basePath' => TESTS_TEMP_DIR . '/cache'));
        $evm->addEventSubscriber($this->_loListener);

        $this->_filesystem1 = new Filesystem(new Local(FILESYSTEM1));
        $this->_filesystem2 = new Filesystem(new Local(FILESYSTEM2));

        $this->_uploadableListener = new UploadableListener(array('filesystems' => array('one' => $this->_filesystem1, 'two' => $this->_filesystem2)));
        $evm->addEventSubscriber($this->_uploadableListener);

        $connectionParams = array(
            'driver'    => 'pdo_sqlite',
            'memory'    => true,
        );

        $config = $this->getMockAnnotatedConfig();
        $conn = DriverManager::getConnection($connectionParams, $config, $evm);
        $em = EntityManager::create($conn, $config, $evm);

        $schema = array_map(function($class) use ($em) {
                return $em->getClassMetadata($class);
            },
            (array) $this->getUsedEntityFixtures()
        );

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($schema);
        $schemaTool->updateSchema($schema, true);

        return $em;
    }

    /**
     * @return array
     */
    abstract protected function getUsedEntityFixtures();
}
