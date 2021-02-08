<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Uploadable\FileHandler\ChainHandler;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ChainHandlerTest extends BaseHandlerTest
{
    protected function setUp(): void
    {
        $this->handler = new ChainHandler();
    }

    public function testImplementation(): void
    {
        $handler = new ChainHandler();
        self::assertInstanceOf(FileHandlerInterface::class, $handler);
    }

    public function testInitializableWithoutHandlers(): void
    {
        $handler = new ChainHandler();
        self::assertInstanceof(ChainHandler::class, $handler);
        $handler = new ChainHandler([]);
        self::assertInstanceof(ChainHandler::class, $handler);
    }

    public function testIsNotInitializableWithWrongHandlers2(): void
    {
        $this->expectException(RuntimeException::class);
        new ChainHandler(['not a handler']);
    }

    public function testIsInitializableWithHandlers(): void
    {
        $handler = new ChainHandler([$this->getHandlerMock()]);
        self::assertInstanceof(ChainHandler::class, $handler);
    }

    public function testPassesCallToHandlersInProperOrder(): void
    {
        $one = $this->getHandlerMock();
        $two = $this->getHandlerMock();
        $three = $this->getHandlerMock();
        $four = $this->getHandlerMock();

        $input = 'someInput';
        $result = 'someResult';
        $name = 'someName';

        $counter = 0;
        $nameCounter = 0;
        $contentCounter = 0;

        $one->method('supports')
            ->with($input)
            ->willReturnCallback(
                static function () use (&$counter) {
                    $counter++;
                    self::assertEquals(1, $counter % 3);

                    return false;
                }
            )
        ;

        $two->method('supports')
            ->with($input)
            ->willReturnCallback(
                static function () use (&$counter) {
                    $counter++;
                    self::assertEquals(2, $counter % 3);

                    return false;
                }
            )
        ;

        $three->method('supports')
            ->with($input)
            ->willReturnCallback(
                static function () use (&$counter) {
                    $counter++;
                    self::assertEquals(0, $counter % 3);

                    return true;
                }
            )
        ;
        $three->expects(self::once())
            ->method('getName')
            ->with($input)
            ->willReturnCallback(
                static function () use (&$nameCounter, &$name) {
                    $nameCounter++;
                    self::assertEquals(1, $nameCounter);

                    return $name;
                }
            )
        ;
        $three->expects(self::once())
            ->method('getContent')
            ->with($input)
            ->willReturnCallback(
                static function () use (&$contentCounter, &$result) {
                    $contentCounter++;
                    self::assertEquals(1, $contentCounter);

                    return $result;
                }
            )
        ;

        // Fourth handler should never be reached, since third supports input.
        $four->expects(self::never())->method(self::anything());

        $handler = new ChainHandler([$one, $two, $three, $four]);
        self::assertTrue($handler->supports($input));
        self::assertEquals($name, $handler->getName($input));
        self::assertEquals($result, $handler->getContent($input));
    }

    /**
     * @return FileHandlerInterface&MockObject
     */
    protected function getHandlerMock(): FileHandlerInterface
    {
        return $this->createMock(FileHandlerInterface::class);
    }
}
