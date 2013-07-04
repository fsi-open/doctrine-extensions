<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Uploadable\FileHandler;

use FSi\DoctrineExtensions\Tests\Uploadable\Utils;
use FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface;

abstract class BaseHandlerTest extends \PHPUnit_Framework_TestCase
{
    const CONTENT = 'sampleContent';

    /**
     * @var \FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface
     */
    protected $handler;

    public function testImplementation()
    {
        $this->assertTrue($this->handler instanceof FileHandlerInterface);
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testGetContentForWrongInputs($input)
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $this->assertNull($this->handler->getContent($input));
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testGetNameForWrongInput($input)
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\RuntimeException');
        $this->assertNull($this->handler->getName($input));
    }

    /**
     * @dataProvider wrongInputs
     */
    public function testNotSupports($input)
    {
        $this->assertFalse($this->handler->supports($input));
    }

    protected function tearDown()
    {
        Utils::deleteRecursive(FILESYSTEM1);
        Utils::deleteRecursive(FILESYSTEM2);
    }

    public static function wrongInputs()
    {
        return array(
            array('not file'),
            array(array()),
            array(new \stdClass()),
            array(42),
        );
    }
}
