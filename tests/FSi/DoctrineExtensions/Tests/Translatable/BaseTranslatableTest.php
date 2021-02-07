<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable;

use DateTime;
use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;

abstract class BaseTranslatableTest extends BaseORMTest
{
    public const SECTION_1 = 'Section 1';
    public const CATEGORY_1 = 'Category 1';
    public const CATEGORY_2 = 'Category 2';
    public const POLISH_TITLE_1 = 'Tytuł polski 1';
    public const POLISH_TITLE_2 = 'Tytuł polski 2';
    public const POLISH_SUBTITLE = 'Podtytuł';
    public const ENGLISH_SUBTITLE = 'A subtitle';
    public const POLISH_TEASER = 'Wstęp polski';
    public const POLISH_CONTENTS_1 = 'Treść artukułu po polsku 1';
    public const POLISH_CONTENTS_2 = 'Treść artukułu po polsku 2';
    public const ENGLISH_TITLE_1 = 'English title 1';
    public const ENGLISH_TITLE_2 = 'English title 2';
    public const ENGLISH_TEASER = 'English teaser';
    public const ENGLISH_CONTENTS_1 = 'English contents of article 1';
    public const ENGLISH_CONTENTS_2 = 'English contents of article 2';
    public const POLISH_COMMENT_1 = 'Treść komentarza 1';
    public const POLISH_COMMENT_2 = 'Treść komentarza 2';

    public const LANGUAGE_PL = 'pl';
    public const LANGUAGE_EN = 'en';
    public const LANGUAGE_DE = 'de';

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getEntityManager();
    }

    protected function createArticle(
        string $title = self::POLISH_TITLE_1,
        string $subtitle = self::POLISH_SUBTITLE,
        string $contents = self::POLISH_CONTENTS_1,
        ?string $locale = null
    ): Article {
        $article = new Article();
        $article->setDate(new DateTime());
        $article->setLocale($locale ?: self::LANGUAGE_PL);
        $article->setTitle($title);
        $article->setSubtitle($subtitle);
        $article->setContents($contents);

        return $article;
    }

    /**
     * @param object $object
     */
    protected function persistAndFlush($object): void
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
