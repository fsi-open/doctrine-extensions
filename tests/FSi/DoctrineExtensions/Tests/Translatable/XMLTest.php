<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use Doctrine\ORM\Mapping\Driver\XmlDriver;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Common\Page;

class XMLTest extends BaseTranslatableTest
{
    const PAGE = 'FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Common\\Page';
    const PAGE_TRANSLATION = 'FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Common\\PageTranslation';

    public function testXMLMapping()
    {
        $this->_logger->enabled = true;

        $page = new Page();
        $page->setLocale($this->_languagePl);
        $page->setContent(self::POLISH_CONTENTS_1);
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_em->persist($page);
        $this->_em->flush();

        $page->setLocale($this->_languageEn);
        $page->setContent(self::ENGLISH_CONTENTS_1);
        $this->_translatableListener->setLocale($this->_languageEn);
        $this->_em->flush();
        $this->_em->refresh($page);

        $this->assertEquals(
            9,
            count($this->_logger->queries),
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
            $page->getTranslation($this->_languagePl)
        );

        $this->assertAttributeEquals(
            self::ENGLISH_CONTENTS_1,
            'content',
            $page->getTranslation($this->_languageEn)
        );
    }

    protected function getMetadataDriverImplementation()
    {
        return new XmlDriver(sprintf('%s/Fixture/XML/config', __DIR__));
    }

    protected function getUsedEntityFixtures()
    {
        return [self::PAGE, self::PAGE_TRANSLATION];
    }
}
