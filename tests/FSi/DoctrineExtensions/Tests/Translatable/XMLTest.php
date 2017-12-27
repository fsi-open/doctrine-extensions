<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Common\Page;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Common\PageTranslation;

class XMLTest extends BaseTranslatableTest
{
    public function testXMLMapping()
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

        $this->assertEquals(
            9,
            count($this->logger->queries),
            'Flushing executed wrong number of queries'
        );

        $translationCount = count($page->getTranslations());
        $this->assertEquals(
            2,
            $translationCount,
            'Number of translations is not valid'
        );

        $this->assertAttributeEquals(
            self::POLISH_CONTENTS_1,
            'content',
            $page->getTranslation(self::LANGUAGE_PL)
        );

        $this->assertAttributeEquals(
            self::ENGLISH_CONTENTS_1,
            'content',
            $page->getTranslation(self::LANGUAGE_EN)
        );
    }

    protected function getMetadataDriverImplementation(): MappingDriver
    {
        return new XmlDriver(sprintf('%s/Fixture/XML/config', __DIR__));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Page::class, PageTranslation::class];
    }
}
