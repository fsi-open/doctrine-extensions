<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable;

use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticlePage;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticleTranslation;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Comment;

class TranslatableCollectionsTest extends BaseTranslatableTest
{
    public const POLISH_ARTICLE_PAGE_TITLE_1 = 'Tytuł strony artykułu 1';
    public const POLISH_ARTICLE_PAGE_TITLE_2 = 'Tytuł strony artykułu 2';

    public function testTranslatedOneToManyCollection()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->logger->enabled = true;

        $article = $this->createArticle();
        $article->addComment(new Comment(self::POLISH_COMMENT_1));
        $article->addComment(new Comment(self::POLISH_COMMENT_2));
        $this->persistAndFlush($article);

        $this->assertEquals(
            6,
            count($this->logger->queries),
            'Incorrect number of performed insert queries'
        );

        $this->logger->queries = [];

        /* @var $article Article */
        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            5,
            count($this->logger->queries),
            'Reloading executed wrong number of queries'
        );

        $translation = $article->getTranslations()[self::LANGUAGE_PL];
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $article->getComments());
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $translation->getComments());
        $this->assertEquals(
            $article->getComments()->count(),
            2,
            'The number of translated object comments is incorrect'
        );
        $this->assertEquals(
            $translation->getComments()->count(),
            2,
            'The number of translation comments is incorrect'
        );
    }

    public function testTranslatedOneToManyRemoval()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $article = $this->createArticle();
        $article->addComment(new Comment(self::POLISH_COMMENT_1));
        $this->persistAndFlush($article);

        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            $article->getComments()->count(),
            1,
            'The number of translated object comments is incorrect'
        );
        $this->assertEquals(
            $article->getTranslations()[self::LANGUAGE_PL]->getComments()->count(),
            1,
            'The number of translation comments is incorrect'
        );

        // Remove the comment
        $this->logger->enabled = true;
        $article->removeComment($article->getComments()->first());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            $article->getComments()->count(),
            0,
            'The number of translated object comments is incorrect'
        );
        $this->assertEquals(
            $article->getTranslations()[self::LANGUAGE_PL]->getComments()->count(),
            0,
            'The number of translation comments is incorrect'
        );
    }

    public function testTranslatedManyToManyCollection()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->logger->enabled = true;

        $article = $this->createArticle();
        $article->addPage(new ArticlePage(self::POLISH_ARTICLE_PAGE_TITLE_1));
        $article->addPage(new ArticlePage(self::POLISH_ARTICLE_PAGE_TITLE_2));
        $this->persistAndFlush($article);

        $this->assertEquals(
            8,
            count($this->logger->queries),
            'Incorrect number of performed insert queries'
        );

        $this->logger->queries = [];

        /* @var $article Article */
        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            5,
            count($this->logger->queries),
            'Reloading executed wrong number of queries'
        );

        $translation = $article->getTranslations()[self::LANGUAGE_PL];
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $article->getPages());
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $translation->getPages());
        $this->assertEquals(
            2,
            $article->getPages()->count(),
            'The number of translated object pages is incorrect'
        );
        $this->assertEquals(
            2,
            $translation->getPages()->count(),
            'The number of translation pages is incorrect'
        );
    }

    public function testTranslatedManyToManyRemoval()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $article = $this->createArticle();
        $article->addPage(new ArticlePage(self::POLISH_ARTICLE_PAGE_TITLE_1));
        $this->persistAndFlush($article);

        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            $article->getPages()->count(),
            1,
            'The number of translated object comments is incorrect'
        );
        $this->assertEquals(
            $article->getTranslations()[self::LANGUAGE_PL]->getPages()->count(),
            1,
            'The number of translation comments is incorrect'
        );

        // Remove the comment
        $this->logger->enabled = true;
        $article->removePage($article->getPages()->first());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            0,
            $article->getPages()->count(),
            'The number of translated object pages is incorrect'
        );
        $this->assertEquals(
            0,
            $article->getTranslations()[self::LANGUAGE_PL]->getPages()->count(),
            'The number of translation pages is incorrect'
        );
    }

    public function testTranslatedUnidirectionalOneToManyCollection()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->logger->enabled = true;

        $article = $this->createArticle();
        $article->addSpecialComment(new Comment(self::POLISH_COMMENT_1));
        $article->addSpecialComment(new Comment(self::POLISH_COMMENT_2));
        $this->persistAndFlush($article);

        $this->assertEquals(
            8,
            count($this->logger->queries),
            'Incorrect number of performed insert queries'
        );

        $this->logger->queries = [];

        /* @var $article Article */
        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            5,
            count($this->logger->queries),
            'Reloading executed wrong number of queries'
        );

        $translation = $article->getTranslations()[self::LANGUAGE_PL];
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $article->getSpecialComments());
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $translation->getSpecialComments());
        $this->assertEquals(
            2,
            $article->getSpecialComments()->count(),
            'The number of translated object pages is incorrect'
        );
        $this->assertEquals(
            2,
            $translation->getSpecialComments()->count(),
            'The number of translation pages is incorrect'
        );
    }

    public function testTranslatedUnidirectionalOneToManyRemoval()
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $article = $this->createArticle();
        $article->addSpecialComment(new Comment(self::POLISH_COMMENT_1));
        $this->persistAndFlush($article);

        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            $article->getSpecialComments()->count(),
            1,
            'The number of translated object comments is incorrect'
        );
        $this->assertEquals(
            $article->getTranslations()[self::LANGUAGE_PL]->getSpecialComments()->count(),
            1,
            'The number of translation comments is incorrect'
        );

        // Remove the comment
        $this->logger->enabled = true;
        $article->removeSpecialComment($article->getSpecialComments()->first());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $article = $this->entityManager->find(Article::class, $article->getId());
        $this->assertEquals(
            0,
            $article->getSpecialComments()->count(),
            'The number of translated object pages is incorrect'
        );
        $this->assertEquals(
            0,
            $article->getTranslations()[self::LANGUAGE_PL]->getSpecialComments()->count(),
            'The number of translation pages is incorrect'
        );
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Comment::class,
            Article::class,
            ArticleTranslation::class,
            ArticlePage::class
        ];
    }
}
