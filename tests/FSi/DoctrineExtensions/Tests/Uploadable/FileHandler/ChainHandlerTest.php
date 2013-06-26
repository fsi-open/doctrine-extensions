<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Uploadable\FileHandler\ChainHandler;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;

class ChainHandlerTest extends \PHPUnit_Framework_TestCase
{
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

    public function testIsNotInitializableWithWrongHandlers1()
    {
        $this->setExpectedException('PHPUnit_Framework_Error');
        new ChainHandler('not array');
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
        $key = 'someKey';
        $filesystem = $this->getMockBuilder('Gaufrette\\Filesystem')->disableOriginalConstructor()->getMock();
        $result = 'someResult';
        $name = 'someName';

        $counter = 0;
        $nameCounter = 0;
        $that = $this;

        $one
            ->expects($this->once())
            ->method('getName')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$nameCounter, $that) {
                    $nameCounter++;
                    $that->assertEquals(1, $nameCounter);
                }
            ))
        ;
        $one
            ->expects($this->once())
            ->method('handle')
            ->with($input, $key, $filesystem)
            ->will($this->returnCallback(
                function() use (&$counter, $that) {
                    $counter++;
                    $that->assertEquals(1, $counter);
                }
            ))
        ;

        $two
            ->expects($this->once())
            ->method('getName')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$nameCounter, $that) {
                    $nameCounter++;
                    $that->assertEquals(2, $nameCounter);
                }
            ))
        ;
        $two
            ->expects($this->once())
            ->method('handle')
            ->with($input, $key, $filesystem)
            ->will($this->returnCallback(
                function() use (&$counter, $that) {
                    $counter++;
                    $that->assertEquals(2, $counter);
                }
            ))
        ;

        $three
            ->expects($this->once())
            ->method('getName')
            ->with($input)
            ->will($this->returnCallback(
                function() use (&$nameCounter, $that, &$name) {
                    $nameCounter++;
                    $that->assertEquals(3, $nameCounter);
                    return $name;
                }
            ))
        ;
        $three
            ->expects($this->once())
            ->method('handle')
            ->with($input, $key, $filesystem)
            ->will($this->returnCallback(
                function() use (&$counter, $that, $result) {
                    $counter++;
                    $that->assertEquals(3, $counter);
                    return $result;
                }
            ))
        ;

        // Fourth handler should never be reached, since third returns not null.
        $four
            ->expects($this->never())
            ->method($this->anything())
        ;

        $handler = new ChainHandler(array($one, $two, $three, $four));
        $this->assertEquals($name, $handler->getName($input));
        $this->assertEquals($result, $handler->handle($input, $key, $filesystem));
    }

    protected function getHandlerMock()
    {
        return $this->getMock('FSi\\DoctrineExtensions\\Uploadable\\FileHandler\\FileHandlerInterface');
    }
}
