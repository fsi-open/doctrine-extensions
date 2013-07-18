<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Mapping\Driver;

use FSi\Component\Metadata\ClassMetadataInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractYamlDriver extends AbstractFileDriver
{
    /**
     * @param ClassMetadataInterface $extendedClassMetadata
     * @return array
     */
    protected function getFileMapping(ClassMetadataInterface $extendedClassMetadata)
    {
        $element = Yaml::parse($this->findMappingFile($extendedClassMetadata));
        if (isset($element[$extendedClassMetadata->getClassName()])) {
            return $element[$extendedClassMetadata->getClassName()];
        } else {
            return array();
        }
    }
}
