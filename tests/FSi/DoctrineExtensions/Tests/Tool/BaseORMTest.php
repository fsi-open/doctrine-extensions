<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Tool;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use FSi\DoctrineExtensions\Translatable\TranslatableListener;
use FSi\DoctrineExtensions\Uploadable\FileHandler;
use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;
use FSi\DoctrineExtensions\Uploadable\UploadableListener;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;
use PHPUnit_Framework_TestCase;

/**
 * This is the base test class for other Doctrine related tests.
 */
abstract class BaseORMTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $_em;

    /**
     * @var TranslatableListener
     */
    protected $_translatableListener;

    /**
     * @var UploadableListener
     */
    protected $_uploadableListener;

    /**
     * @var DebugStack
     */
    protected $_logger;

    /**
     * @var Filesystem
     */
    protected $_filesystem1;

    /**
     * @var Filesystem
     */
    protected $_filesystem2;

    protected function setUp()
    {
        $this->_em = $this->getEntityManager();
    }

    /**
     * Creates default mapping driver.
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getMetadataDriverImplementation()
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * @return Configuration
     */
    protected function getMockAnnotatedConfig()
    {
        $config = $this->createMock('Doctrine\ORM\Configuration');
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(TESTS_TEMP_DIR))
        ;

        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'))
        ;

        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true))
        ;

        $config->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'))
        ;

        $config->expects($this->any())
            ->method('getMetadataCacheImpl')
            ->will($this->returnValue(new ArrayCache()))
        ;

        $config->expects($this->any())
            ->method('getQuoteStrategy')
            ->will($this->returnValue(new DefaultQuoteStrategy()))
        ;

        $config->expects($this->any())
            ->method('getDefaultQueryHints')
            ->will($this->returnValue([]));

        $config->expects($this->any())
            ->method('getRepositoryFactory')
            ->will($this->returnValue(new DefaultRepositoryFactory()))
        ;

        $mappingDriver = $this->getMetadataDriverImplementation();

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver))
        ;

        $config->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'))
        ;

        $this->_logger = new DebugStack();
        $this->_logger->enabled = false;

        $config->expects($this->any())
            ->method('getSQLLogger')
            ->will($this->returnValue($this->_logger))
        ;

        return $config;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        $evm = new EventManager;

        $this->_translatableListener = new TranslatableListener();
        $evm->addEventSubscriber($this->_translatableListener);

        $this->_filesystem1 = new Filesystem(new Local(FILESYSTEM1));
        $this->_filesystem2 = new Filesystem(new Local(FILESYSTEM2));

        $handler = new FileHandler\ChainHandler([
            new FileHandler\GaufretteHandler(),
            new FileHandler\SplFileInfoHandler(),
        ]);
        $keymaker = new Entity();
        $this->_uploadableListener = new UploadableListener(
            ['one' => $this->_filesystem1, 'two' => $this->_filesystem2],
            $handler
        );
        $this->_uploadableListener->setDefaultFilesystem($this->_filesystem1);
        $this->_uploadableListener->setDefaultKeymaker($keymaker);
        $evm->addEventSubscriber($this->_uploadableListener);

        $config = $this->getMockAnnotatedConfig();
        $conn = DriverManager::getConnection(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $config,
            $evm
        );
        $em = EntityManager::create($conn, $config, $evm);

        $schema = array_map(function($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($schema);
        $schemaTool->updateSchema($schema, true);

        return $em;
    }

    /**
     * Get array of classes of entities used in test.
     *
     * @return array
     */
    abstract protected function getUsedEntityFixtures();
}
