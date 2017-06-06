<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\Event;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\Promotion;

class InheritanceTest extends BaseORMTest
{
    const PAGE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Inheritance\\CustomContentPage';
    const EXCERPT_PAGE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Inheritance\\ExcerptContentPage';
    const EVENT_PAGE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Inheritance\\Event';
    const PROMOTION_PAGE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Inheritance\\Promotion';

    const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    public function testUploadablePropertyInMiddleClassInInheritanceTree()
    {
        $event = new Event();
        $event->setCoverImage(new \SplFileInfo(TESTS_PATH . self::TEST_FILE1));
        $event->setColumn1('column 1');
        $event->setColumn2('column 2');
        $event->setTitle('title');
        $event->setExcerpt('excerpt');

        $this->_em->persist($event);
        $this->_em->flush();
        $this->_em->clear();

        $event = $this->_em->find(self::EVENT_PAGE, $event->getId());
        $event->setCoverImage(new \SplFileInfo(TESTS_PATH . self::TEST_FILE2));
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testUploadablePropertyInLeafClassInInheritanceTree()
    {
        $promotion = new Promotion();
        $promotion->setColumn1('column 1');
        $promotion->setColumn2('column 2');
        $promotion->setTitle('title');
        $promotion->setExcerpt('excerpt');

        $this->_em->persist($promotion);
        $this->_em->flush();
        $this->_em->clear();

        $promotion = $this->_em->find(self::PROMOTION_PAGE, $promotion->getId());
        $promotion->setIntroImage(new \SplFileInfo(TESTS_PATH . self::TEST_FILE1));
        $this->_em->flush();
        $this->_em->clear();
        $promotion = $this->_em->find(self::PROMOTION_PAGE, $promotion->getId());

        $this->assertNotNull($promotion->getIntroImage());
    }

    protected function tearDown()
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUsedEntityFixtures()
    {
        return [
            self::PAGE,
            self::EXCERPT_PAGE,
            self::EVENT_PAGE,
            self::PROMOTION_PAGE
        ];
    }
}
