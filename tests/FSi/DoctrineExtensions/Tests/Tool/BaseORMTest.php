<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Tool;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
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
use PHPUnit\Framework\TestCase;
use const FILESYSTEM1;
use const FILESYSTEM2;
use const TESTS_TEMP_DIR;

/**
 * This is the base test class for other Doctrine related tests.
 */
abstract class BaseORMTest extends TestCase
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var TranslatableListener
     */
    protected $translatableListener;

    /**
     * @var UploadableListener
     */
    protected $uploadableListener;

    /**
     * @var SQLLogger
     */
    protected $logger;

    /**
     * @var Filesystem
     */
    protected $filesystem1;

    /**
     * @var Filesystem
     */
    protected $filesystem2;

    protected function setUp()
    {
        $this->entityManager = $this->getEntityManager();
    }

    protected function getMetadataDriverImplementation(): MappingDriver
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * @return Configuration
     */
    protected function getMockAnnotatedConfig()
    {
        $config = $this->createMock(Configuration::class);
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
            ->will($this->returnValue(ClassMetadataFactory::class))
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
            ->will($this->returnValue(EntityRepository::class))
        ;

        $this->logger = new DebugStack();
        $this->logger->enabled = false;

        $config->expects($this->any())
            ->method('getSQLLogger')
            ->will($this->returnValue($this->logger))
        ;

        return $config;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $evm = new EventManager();

        $this->translatableListener = new TranslatableListener();
        $evm->addEventSubscriber($this->translatableListener);

        $this->filesystem1 = new Filesystem(new Local(FILESYSTEM1));
        $this->filesystem2 = new Filesystem(new Local(FILESYSTEM2));

        $handler = new FileHandler\ChainHandler([
            new FileHandler\GaufretteHandler(),
            new FileHandler\SplFileInfoHandler(),
        ]);
        $keymaker = new Entity();
        $this->uploadableListener = new UploadableListener(
            ['one' => $this->filesystem1, 'two' => $this->filesystem2],
            $handler
        );
        $this->uploadableListener->setDefaultFilesystem($this->filesystem1);
        $this->uploadableListener->setDefaultKeymaker($keymaker);
        $evm->addEventSubscriber($this->uploadableListener);

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
