<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Uploadable\Mapping\Driver\Annotation;
use FSi\DoctrineExtensions\Uploadable\Mapping\Driver\SimplifiedXml;
use FSi\DoctrineExtensions\Uploadable\Mapping\Driver\SimplifiedYaml;
use FSi\DoctrineExtensions\Uploadable\Mapping\Driver\Xml;
use FSi\DoctrineExtensions\Uploadable\Mapping\Driver\Yaml;
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
        return [
            [Xml::class],
            [Yaml::class],
            [Annotation::class],
            [SimplifiedXml::class],
            [SimplifiedYaml::class],
        ];
    }
}
