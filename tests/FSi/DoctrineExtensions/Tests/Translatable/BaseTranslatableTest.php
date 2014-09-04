<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

abstract class BaseTranslatableTest extends BaseORMTest
{
    const CATEGORY = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Category";
    const SECTION = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Section";
    const COMMENT = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Comment";
    const ARTICLE = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\Article";
    const ARTICLE_TRANSLATION = "FSi\\DoctrineExtensions\\Tests\\Translatable\\Fixture\\ArticleTranslation";

    const SECTION_1 = 'Section 1';
    const CATEGORY_1 = 'Category 1';
    const CATEGORY_2 = 'Category 2';
    const POLISH_TITLE_1 = 'Tytuł polski 1';
    const POLISH_TITLE_2 = 'Tytuł polski 2';
    const POLISH_TEASER = 'Wstep polski';
    const POLISH_CONTENTS_1 = 'Treść artukułu po polsku 1';
    const POLISH_CONTENTS_2 = 'Treść artukułu po polsku 2';
    const ENGLISH_TITLE_1 = 'English title 1';
    const ENGLISH_TITLE_2 = 'English title 2';
    const ENGLISH_TEASER = 'English teaser';
    const ENGLISH_CONTENTS_1 = 'English contents of article 1';
    const ENGLISH_CONTENTS_2 = 'English contents of article 2';

    protected $_languagePl = 'pl';
    protected $_languageEn = 'en';
    protected $_languageDe = 'de';

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->getEntityManager();
    }
}
