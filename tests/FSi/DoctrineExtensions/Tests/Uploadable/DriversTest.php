<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use PHPUnit\Framework\TestCase;

class DriversTest extends TestCase
{
    /**
     * @dataProvider drivers
     */
    public function testDrivers(string $class)
    {
        $this->assertTrue(class_exists($class));
    }

    public static function drivers()
    {
        $base = 'FSi\\DoctrineExtensions\\Uploadable\\Mapping\\Driver\\';

        return [
            [$base . 'Xml'],
            [$base . 'Yaml'],
            [$base . 'Annotation'],
            [$base . 'SimplifiedXml'],
            [$base . 'SimplifiedYaml'],
        ];
    }
}
