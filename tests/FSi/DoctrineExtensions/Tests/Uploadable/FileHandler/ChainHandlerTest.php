<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\Exception\RuntimeException;
use FSi\DoctrineExtensions\Uploadable\FileHandler\ChainHandler;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;

class ChainHandlerTest extends BaseHandlerTest
{
    protected function setUp()
    {
        $this->handler = new ChainHandler();
    }

    public function testImplementation()
    {
        $handler = new ChainHandler();
        $this->assertTrue($handler instanceof FileHandlerInterface);
    }

    public function testInitializableWithoutHandlers()
    {
        $handler = new ChainHandler();
        $this->assertInstanceof(ChainHandler::class, $handler);
        $handler = new ChainHandler([]);
        $this->assertInstanceof(ChainHandler::class, $handler);

    }

    public function testIsNotInitializableWithWrongHandlers2()
    {
        $this->expectException(RuntimeException::class);
        new ChainHandler(['not a handler']);
    }

    public function testIsInitializableWithHandlers()
    {
        $handler = new ChainHandler([$this->getHandlerMock()]);
        $this->assertInstanceof(ChainHandler::class, $handler);
    }

    public function testPassesCallToHandlersInProperOrder()
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
        $that = $this;

        $one->expects($this->any())
            ->method('supports')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$counter, $that) {
                    $counter++;
                    $that->assertEquals(1, $counter % 3);
                    return false;
                }
            ))
        ;

        $two->expects($this->any())
            ->method('supports')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$counter, $that) {
                    $counter++;
                    $that->assertEquals(2, $counter % 3);
                    return false;
                }
            ))
        ;

        $three->expects($this->any())
            ->method('supports')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$counter, $that, $result) {
                    $counter++;
                    $that->assertEquals(0, $counter % 3);
                    return true;
                }
            ))
        ;
        $three->expects($this->once())
            ->method('getName')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$nameCounter, $that, &$name) {
                    $nameCounter++;
                    $that->assertEquals(1, $nameCounter);
                    return $name;
                }
            ))
        ;
        $three->expects($this->once())
            ->method('getContent')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$contentCounter, $that, &$result) {
                    $contentCounter++;
                    $that->assertEquals(1, $contentCounter);
                    return $result;
                }
            ))
        ;

        // Fourth handler should never be reached, since third supports input.
        $four->expects($this->never())->method($this->anything());

        $handler = new ChainHandler([$one, $two, $three, $four]);
        $this->assertTrue($handler->supports($input));
        $this->assertEquals($name, $handler->getName($input));
        $this->assertEquals($result, $handler->getContent($input));
    }

    protected function getHandlerMock(): FileHandlerInterface
    {
        return $this->createMock(FileHandlerInterface::class);
    }
}
