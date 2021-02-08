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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
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

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
    }

    protected function getMetadataDriverImplementation(): MappingDriver
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    protected function getMockAnnotatedConfig(): Configuration
    {
        $config = $this->createMock(Configuration::class);
        $config->expects(self::once())->method('getProxyDir')->willReturn(TESTS_TEMP_DIR);
        $config->expects(self::once())->method('getProxyNamespace')->willReturn('Proxy');
        $config->expects(self::once())->method('getAutoGenerateProxyClasses')->willReturn(true);
        $config->expects(self::once())->method('getClassMetadataFactoryName')->willReturn(ClassMetadataFactory::class);
        $config->method('getMetadataCacheImpl')->willReturn(new ArrayCache());
        $config->method('getQuoteStrategy')->willReturn(new DefaultQuoteStrategy());
        $config->method('getDefaultQueryHints')->willReturn([]);
        $config->method('getRepositoryFactory')->willReturn(new DefaultRepositoryFactory());

        $mappingDriver = $this->getMetadataDriverImplementation();
        $config->method('getMetadataDriverImpl')->willReturn($mappingDriver);
        $config->method('getDefaultRepositoryClassName')->willReturn(EntityRepository::class);

        $this->logger = new DebugStack();
        $this->logger->enabled = false;
        $config->method('getSQLLogger')->willReturn($this->logger);

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
        $conn = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config, $evm);
        $em = EntityManager::create($conn, $config, $evm);

        $schema = array_map(
            static function (string $class) use ($em): ClassMetadata {
                return $em->getClassMetadata($class);
            },
            $this->getUsedEntityFixtures()
        );

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($schema);
        $schemaTool->updateSchema($schema, true);

        return $em;
    }

    abstract protected function getUsedEntityFixtures(): array;
}
