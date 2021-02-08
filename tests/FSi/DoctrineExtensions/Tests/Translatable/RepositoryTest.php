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
use Doctrine\ORM\NoResultException;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticlePage;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\ArticleTranslation;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Category;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Comment;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Section;
use FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository;

class RepositoryTest extends BaseTranslatableTest
{
    /**
     * Test if call to getTranslation creates non existent translations
     */
    public function testCreatingNonExistentTranslationThroughRepository(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $repository = $this->entityManager->getRepository(Article::class);
        $article = new Article();
        $article->setDate(new DateTime());
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $translationEn = $repository->getTranslation($article, self::LANGUAGE_EN);
        self::assertTrue($article->getTranslations()->contains($translationEn));

        $translationPl = $repository->getTranslation($article, self::LANGUAGE_PL);
        self::assertTrue($article->getTranslations()->contains($translationPl));

        self::assertSame($translationEn, $article->getTranslations()->get(self::LANGUAGE_EN));
        self::assertSame($translationPl, $article->getTranslations()->get(self::LANGUAGE_PL));
        self::assertSame($translationPl, $repository->getTranslation($article, self::LANGUAGE_PL));
    }

    /**
     * Test if call to hasTranslation returns true for existing translations
     * and false otherwise
     */
    public function testCheckingIfTranslationExistsThroughRepository(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $repository = $this->entityManager->getRepository(Article::class);
        $article = new Article();
        $article->setDate(new DateTime());
        $translationEn = $repository->getTranslation($article, self::LANGUAGE_EN);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $this->entityManager->persist($article);
        $this->entityManager->persist($translationEn);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $article = $repository->find($article->getId());
        self::assertTrue($repository->hasTranslation($article, self::LANGUAGE_EN));
        self::assertFalse($repository->hasTranslation($article, self::LANGUAGE_PL));
    }

    public function testNotOverwritingTranslationForNewObject(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $repository = $this->entityManager->getRepository(Article::class);
        $article = new Article();
        $article->setDate(new DateTime());

        $translationEn = $repository->getTranslation($article, self::LANGUAGE_EN);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $translationPl = $repository->getTranslation($article, self::LANGUAGE_PL);
        $translationPl->setTitle(self::POLISH_TITLE_1);
        $translationPl->setContents(self::POLISH_CONTENTS_1);

        $this->entityManager->persist($translationEn);
        $this->entityManager->persist($translationPl);
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $this->entityManager->refresh($article);

        self::assertCount(2, $article->getTranslations(), 'Number of translations is not valid');
        self::assertEquals(self::ENGLISH_TITLE_1, $article->getTitle());
        self::assertEquals(self::ENGLISH_CONTENTS_1, $article->getContents());
    }

    /**
     * Test if query builder returned by translatable repository has join to translation entity
     * and is constrained to current locale
     */
    public function testTranslatableRepositoryCreateQueryBuilder(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $qb = $this->entityManager->getRepository(Article::class)->createTranslatableQueryBuilder('a', 't');
        self::assertEquals(
            sprintf(
                'SELECT a, t, dt FROM %s a LEFT JOIN a.translations t WITH t.locale = :locale'
                    . ' LEFT JOIN a.translations dt WITH dt.locale = :deflocale',
                Article::class
            ),
            $qb->getQuery()->getDql(),
            'Wrong DQL returned from QueryBuilder'
        );

        self::assertEquals(
            self::LANGUAGE_PL,
            $qb->getParameter('locale')->getValue(),
            'Parameter :locale has wrong value'
        );

        self::assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('deflocale')->getValue(),
            'Parameter :deflocale has wrong value'
        );
    }

    /**
     * Test if query builder returned by translatable repository has join to translation entity
     * and is constrained to current locale
     */
    public function testTranslatableRepositoryCreateQueryBuilderWithLocaleSameAsDefaultLocale(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $qb = $this->entityManager->getRepository(Article::class)->createTranslatableQueryBuilder('a', 't');
        self::assertEquals(
            sprintf(
                'SELECT a, t FROM %s a LEFT JOIN a.translations t WITH t.locale = :locale',
                Article::class
            ),
            $qb->getQuery()->getDql(),
            'Wrong DQL returned from QueryBuilder'
        );

        self::assertEquals(
            self::LANGUAGE_EN,
            $qb->getParameter('locale')->getValue(),
            'Parameter :locale has wrong value'
        );
    }

    public function testPostHydrateWithTranslatableQueryBuilder(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_EN);
        $repository = $this->entityManager->getRepository(Article::class);
        $article = new Article();
        $article->setDate(new DateTime());

        $translationEn = $repository->getTranslation($article, self::LANGUAGE_EN);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $translationPl = $repository->getTranslation($article, self::LANGUAGE_PL);
        $translationPl->setTitle(self::POLISH_TITLE_1);
        $translationPl->setContents(self::POLISH_CONTENTS_1);

        $this->entityManager->persist($translationEn);
        $this->entityManager->persist($translationPl);
        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->logger->enabled = true;
        $query = $repository->createTranslatableQueryBuilder('a', 't', 'dt')->getQuery();

        $articles = $query->execute();
        foreach ($articles as $article) {
            self::assertEquals(self::ENGLISH_TITLE_1, $article->getTitle());
            self::assertEquals(self::ENGLISH_CONTENTS_1, $article->getContents());
        }

        self::assertCount(4, $this->logger->queries, 'Reloading executed wrong number of queries');
    }

    /**
     * tests that findTranslatableBy will return correct entity
     */
    public function testFindTranslatableByFields(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->entityManager->getRepository(Article::class);
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['title' => self::CATEGORY_1]);
        $section = $this->entityManager->getRepository(Section::class)->findOneBy(['title' => self::SECTION_1]);
        $comment = $this->entityManager->getRepository(Comment::class)->findOneBy(['content' => 'Ipsum']);

        $this->logger->enabled = true;
        /** @var Article $article */
        $articles = $repository->findTranslatableBy([
            'date' => '2014-02-02 00:00:00', //field in Article, not translated
            'title' => self::POLISH_TITLE_1, //field in ArticleTranslation with same name
            'teaser' => self::POLISH_TEASER, //field in ArticleTranslation with different name
            'section' => $section, //field in Article - single value association
            'categories' => $category, //field in Article - collection value association
            'comments' => $comment, //translatable property in Article - one to many association
        ], ['date' => 'ASC', 'title' => 'DESC']);

        self::assertCount(1, $articles);
        self::assertEquals(self::LANGUAGE_PL, $articles[0]->getLocale());
        self::assertEquals(self::POLISH_TITLE_1, $articles[0]->getTitle());
        self::assertEquals(self::POLISH_TEASER, $articles[0]->getTeaser());
        self::assertEquals(self::POLISH_CONTENTS_1, $articles[0]->getContents());
    }

    /**
     * test that findTranslatableBy will return fields from default translation
     * if translation in current locale was not found
     */
    public function testFindTranslatableByLocaleFallback(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->entityManager->getRepository(Article::class);

        /** @var Article $article */
        $articles = $repository->findTranslatableBy(['date' => '2014-01-01 00:00:00']);

        self::assertEquals(self::ENGLISH_TITLE_1, $articles[0]->getTitle());
        self::assertEquals(self::ENGLISH_TEASER, $articles[0]->getTeaser());
        self::assertEquals(self::ENGLISH_CONTENTS_1, $articles[0]->getContents());
    }

    /**
     * test that findTranslatableBy will return fields from translation in specified locale
     */
    public function testFindTranslatableByCustomLocale(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_DE);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->entityManager->getRepository(Article::class);

        /** @var Article $article */
        $articles = $repository->findTranslatableBy(
            ['date' => '2014-01-01 00:00:00'],
            null,
            null,
            null,
            self::LANGUAGE_PL
        );

        self::assertEquals(self::ENGLISH_TITLE_1, $articles[0]->getTitle());
        self::assertEquals(self::ENGLISH_TEASER, $articles[0]->getTeaser());
        self::assertEquals(self::ENGLISH_CONTENTS_1, $articles[0]->getContents());
    }

    /**
     * tests that findTranslatableOneBy will return correct entity and if not found throw exception
     */
    public function testFindTranslatableOneByFields(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->entityManager->getRepository(Article::class);
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['title' => self::CATEGORY_1]);
        $section = $this->entityManager->getRepository(Section::class)->findOneBy(['title' => self::SECTION_1]);
        $comment1 = $this->entityManager->getRepository(Comment::class)->findOneBy(['content' => 'Ipsum']);
        $comment2 = $this->entityManager->getRepository(Comment::class)->findOneBy(['content' => 'Lorem']);

        /** @var Article $article */
        $article = $repository->findTranslatableOneBy([
            'date' => '2014-02-02 00:00:00', //field in Article, not translated
            'title' => self::POLISH_TITLE_1, //field in ArticleTranslation with same name
            'teaser' => self::POLISH_TEASER, //field in ArticleTranslation with different name
            'section' => $section, //field in Article - single value association
            'categories' => $category, //field in Article - collection value association
            'comments' => [$comment1, $comment2], //translatable property in Article - one to many association
        ]);

        self::assertEquals(self::LANGUAGE_PL, $article->getLocale());
        self::assertEquals(self::POLISH_TITLE_1, $article->getTitle());
        self::assertEquals(self::POLISH_TEASER, $article->getTeaser());
        self::assertEquals(self::POLISH_CONTENTS_1, $article->getContents());

        $this->expectException(NoResultException::class);
        // value that not exists
        $repository->findTranslatableOneBy(['date' => '2014-01-01 00:00:01']);
    }

    /**
     * test that findTranslatableOneBy will return fields from default translation
     * if translation in current locale was not found
     */
    public function testFindTranslatableOneByLocaleFallback(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_PL);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->entityManager->getRepository(Article::class);

        /** @var Article $article */
        $article = $repository->findTranslatableOneBy(['date' => '2014-01-01 00:00:00']);
        self::assertEquals(self::ENGLISH_TITLE_1, $article->getTitle());
        self::assertEquals(self::ENGLISH_TEASER, $article->getTeaser());
        self::assertEquals(self::ENGLISH_CONTENTS_1, $article->getContents());
    }

    /**
     * test that findTranslatableOneBy will return fields from translation in specified locale
     */
    public function testFindTranslatableOneByWithCustomLocale(): void
    {
        $this->translatableListener->setLocale(self::LANGUAGE_DE);
        $this->translatableListener->setDefaultLocale(self::LANGUAGE_EN);

        $this->fillDataForFindTranslatable();

        /** @var TranslatableRepository $repository */
        $repository = $this->entityManager->getRepository(Article::class);

        /** @var Article $article */
        $article = $repository->findTranslatableOneBy(
            ['date' => '2014-01-01 00:00:00'],
            null,
            self::LANGUAGE_PL
        );

        self::assertEquals(self::ENGLISH_TITLE_1, $article->getTitle());
        self::assertEquals(self::ENGLISH_TEASER, $article->getTeaser());
        self::assertEquals(self::ENGLISH_CONTENTS_1, $article->getContents());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Category::class,
            Section::class,
            Comment::class,
            Article::class,
            ArticleTranslation::class,
            ArticlePage::class
        ];
    }

    private function fillDataForFindTranslatable(): void
    {
        /** @var TranslatableRepository $repository */
        $repository = $this->entityManager->getRepository(Article::class);

        $article1 = new Article();
        $this->entityManager->persist($article1);
        $article1->setDate(new DateTime('2014-01-01 00:00:00'));
        $translationEn = $repository->getTranslation($article1, self::LANGUAGE_EN);
        $translationEn->setTitle(self::ENGLISH_TITLE_1);
        $translationEn->setIntroduction(self::ENGLISH_TEASER);
        $translationEn->setContents(self::ENGLISH_CONTENTS_1);
        $this->entityManager->persist($translationEn);

        $article2 = new Article();
        $this->entityManager->persist($article2);
        $article2->setDate(new DateTime('2014-02-02 00:00:00'));
        $translationPl = $repository->getTranslation($article2, self::LANGUAGE_PL);
        $translationPl->setTitle(self::POLISH_TITLE_1);
        $translationPl->setIntroduction(self::POLISH_TEASER);
        $translationPl->setContents(self::POLISH_CONTENTS_1);
        $this->entityManager->persist($translationPl);

        $category1 = new Category();
        $category1->setTitle(self::CATEGORY_1);
        $article1->addCategory($category1);
        $article2->addCategory($category1);
        $this->entityManager->persist($category1);

        $section = new Section();
        $section->setTitle(self::SECTION_1);
        $article1->setSection($section);
        $article2->setSection($section);
        $this->entityManager->persist($section);
        $translationPl->addComment(new Comment('Lorem'));
        $translationPl->addComment(new Comment('Ipsum'));

        $this->entityManager->flush();
        $this->entityManager->refresh($article1);
        $this->entityManager->refresh($article2);
    }
}
