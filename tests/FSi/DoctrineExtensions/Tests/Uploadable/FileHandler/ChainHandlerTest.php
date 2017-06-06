<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

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
        new ChainHandler();
        new ChainHandler(array());
    }

    public function testIsNotInitializableWithWrongHandlers2()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        new ChainHandler(array('not handler'));
    }

    public function testIsInitializableWithHandlers()
    {
        new ChainHandler(array($this->getHandlerMock()));
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

        $handler = new ChainHandler(array($one, $two, $three, $four));
        $this->assertTrue($handler->supports($input));
        $this->assertEquals($name, $handler->getName($input));
        $this->assertEquals($result, $handler->getContent($input));
    }

    protected function getHandlerMock()
    {
        return $this->getMock('FSi\\DoctrineExtensions\\Uploadable\\FileHandler\\FileHandlerInterface');
    }
}
