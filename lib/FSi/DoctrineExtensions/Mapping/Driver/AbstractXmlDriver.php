<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Mapping\Driver;

use FSi\Component\Metadata\ClassMetadataInterface;
use SimpleXmlElement;

abstract class AbstractXmlDriver extends AbstractFileDriver
{
    const DOCTRINE_NAMESPACE_URI = 'http://doctrine-project.org/schemas/orm/doctrine-mapping';

    /**
     * @param ClassMetadataInterface $extendedClassMetadata
     * @return SimpleXmlElement|null
     */
    protected function getFileMapping(ClassMetadataInterface $extendedClassMetadata)
    {
        $xmlElement = simplexml_load_file($this->findMappingFile($extendedClassMetadata));
        $xmlElement = $xmlElement->children(self::DOCTRINE_NAMESPACE_URI);

        $className = $extendedClassMetadata->getClassName();
        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                if ($this->getAttribute($entityElement, 'name') == $className) {
                    return $entityElement;
                }
            }
        } elseif (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                if ($this->getAttribute($mappedSuperClass, 'name') == $className) {
                    return $mappedSuperClass;
                }
            }
        }
    }

    /**
     * @param SimpleXmlElement $node
     * @param string $name
     * @return string
     */
    protected function getAttribute(SimpleXmlElement $node, $name)
    {
        $attributes = $node->attributes();
        if (!isset($attributes[$name])) {
            return;
        }
        return (string) $attributes[$name];
    }
}
