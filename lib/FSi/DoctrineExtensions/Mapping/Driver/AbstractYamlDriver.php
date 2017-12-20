<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Mapping\Driver;

use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractYamlDriver extends AbstractFileDriver
{
    protected function getFileMapping(ClassMetadataInterface $extendedClassMetadata): array
    {
        $element = Yaml::parse(file_get_contents($this->findMappingFile($extendedClassMetadata)));
        if (isset($element[$extendedClassMetadata->getClassName()])) {
            return $element[$extendedClassMetadata->getClassName()];
        }

        return [];
    }
}
