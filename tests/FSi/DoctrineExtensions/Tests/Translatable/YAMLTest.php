<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable;

use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Common\Page;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Common\PageTranslation;

class YAMLTest extends BaseTranslatableTest
{
    public function testYAMLMapping(): void
    {
        $this->logger->enabled = true;

        $page = new Page();
        $page->setLocale(self::LANGUAGE_PL);
        $page->setContent(self::POLISH_CONTENTS_1);
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $page->setLocale(self::LANGUAGE_EN);
        $page->setContent(self::ENGLISH_CONTENTS_1);
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $this->entityManager->flush();
        $this->entityManager->refresh($page);

        self::assertCount(9, $this->logger->queries, 'Flushing executed wrong number of queries');
        self::assertCount(2, $page->getTranslations(), 'Number of translations is not valid');
        self::assertEquals(self::POLISH_CONTENTS_1, $page->getTranslation(self::LANGUAGE_PL)->getContent());
        self::assertEquals(self::ENGLISH_CONTENTS_1, $page->getTranslation(self::LANGUAGE_EN)->getContent());
    }

    protected function getMetadataDriverImplementation(): MappingDriver
    {
        return new YamlDriver(sprintf('%s/Fixture/YAML/config', __DIR__));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Page::class, PageTranslation::class];
    }
}
