<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\CustomContentPage;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\Employee;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\Event;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\ExcerptContentPage;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\Person;
use FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance\Promotion;
use SplFileInfo;

class InheritanceTest extends BaseORMTest
{
    public const TEST_FILE1 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/penguins.jpg';
    public const TEST_FILE2 = '/FSi/DoctrineExtensions/Tests/Uploadable/Fixture/lighthouse.jpg';

    public function testUploadablePropertyInMiddleClassInInheritanceTree()
    {
        $event = new Event();
        $event->setCoverImage(new SplFileInfo(TESTS_PATH . self::TEST_FILE1));
        $event->setColumn1('column 1');
        $event->setColumn2('column 2');
        $event->setTitle('title');
        $event->setExcerpt('excerpt');

        $this->entityManager->persist($event);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $event = $this->entityManager->find(Event::class, $event->getId());
        $event->setCoverImage(new SplFileInfo(TESTS_PATH . self::TEST_FILE2));
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function testUploadablePropertyInLeafClassInInheritanceTree()
    {
        $promotion = new Promotion();
        $promotion->setColumn1('column 1');
        $promotion->setColumn2('column 2');
        $promotion->setTitle('title');
        $promotion->setExcerpt('excerpt');

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $promotion = $this->entityManager->find(Promotion::class, $promotion->getId());
        $promotion->setIntroImage(new SplFileInfo(TESTS_PATH . self::TEST_FILE1));
        $this->entityManager->flush();
        $this->entityManager->clear();
        $promotion = $this->entityManager->find(Promotion::class, $promotion->getId());

        $this->assertNotNull($promotion->getIntroImage());
    }

    public function testUploadablePropertyInSingleTableInInheritance()
    {
        $employee = new Employee();
        $employee->setFile(new SplFileInfo(TESTS_PATH . self::TEST_FILE1));

        $this->entityManager->persist($employee);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $employee = $this->entityManager->find(Employee::class, $employee->getId());

        $this->assertNotNull($employee->getFile());
    }

    protected function tearDown(): void
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            CustomContentPage::class,
            ExcerptContentPage::class,
            Event::class,
            Promotion::class,
            Person::class,
            Employee::class
        ];
    }
}
