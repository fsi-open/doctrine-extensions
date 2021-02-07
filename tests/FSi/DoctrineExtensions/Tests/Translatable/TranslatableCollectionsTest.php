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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

class TranslatableCollectionsTest extends BaseTranslatableTest
{
    public const POLISH_ARTICLE_PAGE_TITLE_1 = 'Tytuł strony artykułu 1';
    public const POLISH_ARTICLE_PAGE_TITLE_2 = 'Tytuł strony artykułu 2';

    public function testTranslatedOneToManyCollection(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->logger->enabled = true;

        $article = $this->createArticle();
        $article->addComment(new Comment(self::POLISH_COMMENT_1));
        $article->addComment(new Comment(self::POLISH_COMMENT_2));
        $this->persistAndFlush($article);

        self::assertCount(6, $this->logger->queries, 'Incorrect number of performed insert queries');

        $this->logger->queries = [];

        /* @var $article Article */
        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertCount(5, $this->logger->queries, 'Reloading executed wrong number of queries');

        $translation = $article->getTranslations()[self::LANGUAGE_PL];
        self::assertInstanceOf(ArrayCollection::class, $article->getComments());
        self::assertInstanceOf(PersistentCollection::class, $translation->getComments());
        self::assertEquals(
            2,
            $article->getComments()->count(),
            'The number of translated object comments is incorrect'
        );
        self::assertEquals(
            2,
            $translation->getComments()->count(),
            'The number of translation comments is incorrect'
        );
    }

    public function testTranslatedOneToManyRemoval(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $article = $this->createArticle();
        $article->addComment(new Comment(self::POLISH_COMMENT_1));
        $this->persistAndFlush($article);

        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertEquals(
            1,
            $article->getComments()->count(),
            'The number of translated object comments is incorrect'
        );
        self::assertEquals(
            1,
            $article->getTranslations()[self::LANGUAGE_PL]->getComments()->count(),
            'The number of translation comments is incorrect'
        );

        // Remove the comment
        $this->logger->enabled = true;
        $article->removeComment($article->getComments()->first());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertEquals(
            0,
            $article->getComments()->count(),
            'The number of translated object comments is incorrect'
        );
        self::assertEquals(
            0,
            $article->getTranslations()[self::LANGUAGE_PL]->getComments()->count(),
            'The number of translation comments is incorrect'
        );
    }

    public function testTranslatedManyToManyCollection(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->logger->enabled = true;

        $article = $this->createArticle();
        $article->addPage(new ArticlePage(self::POLISH_ARTICLE_PAGE_TITLE_1));
        $article->addPage(new ArticlePage(self::POLISH_ARTICLE_PAGE_TITLE_2));
        $this->persistAndFlush($article);

        self::assertCount(8, $this->logger->queries, 'Incorrect number of performed insert queries');

        $this->logger->queries = [];

        /* @var $article Article */
        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertCount(5, $this->logger->queries, 'Reloading executed wrong number of queries');

        $translation = $article->getTranslations()[self::LANGUAGE_PL];
        self::assertInstanceOf(ArrayCollection::class, $article->getPages());
        self::assertInstanceOf(PersistentCollection::class, $translation->getPages());
        self::assertEquals(2, $article->getPages()->count(), 'The number of translated object pages is incorrect');
        self::assertEquals(2, $translation->getPages()->count(), 'The number of translation pages is incorrect');
    }

    public function testTranslatedManyToManyRemoval(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);

        $article = $this->createArticle();
        $article->addPage(new ArticlePage(self::POLISH_ARTICLE_PAGE_TITLE_1));
        $this->persistAndFlush($article);

        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertEquals(
            1,
            $article->getPages()->count(),
            'The number of translated object comments is incorrect'
        );
        self::assertEquals(
            1,
            $article->getTranslations()[self::LANGUAGE_PL]->getPages()->count(),
            'The number of translation comments is incorrect'
        );

        // Remove the comment
        $this->logger->enabled = true;
        $article->removePage($article->getPages()->first());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertEquals(
            0,
            $article->getPages()->count(),
            'The number of translated object pages is incorrect'
        );
        self::assertEquals(
            0,
            $article->getTranslations()[self::LANGUAGE_PL]->getPages()->count(),
            'The number of translation pages is incorrect'
        );
    }

    public function testTranslatedUnidirectionalOneToManyCollection(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->logger->enabled = true;

        $article = $this->createArticle();
        $article->addSpecialComment(new Comment(self::POLISH_COMMENT_1));
        $article->addSpecialComment(new Comment(self::POLISH_COMMENT_2));
        $this->persistAndFlush($article);

        self::assertCount(8, $this->logger->queries, 'Incorrect number of performed insert queries');

        $this->logger->queries = [];

        /* @var $article Article */
        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertCount(5, $this->logger->queries, 'Reloading executed wrong number of queries');

        $translation = $article->getTranslations()[self::LANGUAGE_PL];
        self::assertInstanceOf(ArrayCollection::class, $article->getSpecialComments());
        self::assertInstanceOf(PersistentCollection::class, $translation->getSpecialComments());
        self::assertEquals(
            2,
            $article->getSpecialComments()->count(),
            'The number of translated object pages is incorrect'
        );
        self::assertEquals(
            2,
            $translation->getSpecialComments()->count(),
            'The number of translation pages is incorrect'
        );
    }

    public function testTranslatedUnidirectionalOneToManyRemoval(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $article = $this->createArticle();
        $article->addSpecialComment(new Comment(self::POLISH_COMMENT_1));
        $this->persistAndFlush($article);

        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertEquals(
            1,
            $article->getSpecialComments()->count(),
            'The number of translated object comments is incorrect'
        );
        self::assertEquals(
            1,
            $article->getTranslations()[self::LANGUAGE_PL]->getSpecialComments()->count(),
            'The number of translation comments is incorrect'
        );

        // Remove the comment
        $this->logger->enabled = true;
        $article->removeSpecialComment($article->getSpecialComments()->first());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $article = $this->entityManager->find(Article::class, $article->getId());
        self::assertEquals(
            0,
            $article->getSpecialComments()->count(),
            'The number of translated object pages is incorrect'
        );
        self::assertEquals(
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
