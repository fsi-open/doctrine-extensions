<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Mapping\Driver;

use DOMDocument;
use DOMXPath;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;
use ReflectionClass;
use SimpleXmlElement;

abstract class AbstractXmlDriver extends AbstractFileDriver
{
    const DOCTRINE_NAMESPACE_URI = 'http://doctrine-project.org/schemas/orm/doctrine-mapping';
    const FSI_NAMESPACE_URI = 'http://fsi.pl/schemas/orm/doctrine-extensions-mapping';

    protected function getFileMapping(ClassMetadataInterface $extendedClassMetadata): ?SimpleXmlElement
    {
        $fileLocation = $this->findMappingFile($extendedClassMetadata);
        $dom = new DOMDocument();
        $dom->load($fileLocation);
        if (!$this->validateFile($dom)) {
            throw new MappingException(sprintf(
                'There are wrong mappings in XML mapping for class "%s" in file "%s"',
                $extendedClassMetadata->getClassName(),
                $fileLocation
            ));
        }

        $xmlElement = simplexml_load_file($fileLocation);
        $xmlElement = $xmlElement->children(self::DOCTRINE_NAMESPACE_URI);

        $className = $extendedClassMetadata->getClassName();
        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                if ($this->getAttribute($entityElement, 'name') === $className) {
                    return $entityElement;
                }
            }
        } elseif (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                if ($this->getAttribute($mappedSuperClass, 'name') === $className) {
                    return $mappedSuperClass;
                }
            }
        }

        return null;
    }

    protected function getAttribute(SimpleXmlElement $node, string $name): ?string
    {
        $attributes = $node->attributes();
        if (!isset($attributes[$name])) {
            return null;
        }

        return (string) $attributes[$name];
    }

    private function validateFile(DOMDocument $dom): bool
    {
        // Schemas for validation.
        $schemaLocations = [
            'http://fsi.pl/schemas/orm/doctrine-extensions-mapping'
                => dirname(__DIR__, 5) . '/doctrine-extensions-mapping.xsd',
            'http://doctrine-project.org/schemas/orm/doctrine-mapping' => $this->getDoctrineSchemePath(),
        ];

        // Elements from unknown namespaces are removed before validation.
        $known = [
            'http://fsi.pl/schemas/orm/doctrine-extensions-mapping',
            'http://doctrine-project.org/schemas/orm/doctrine-mapping',
            'http://www.w3.org/XML/1998/namespace',
            'http://www.w3.org/2001/XMLSchema-instance',
        ];
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('namespace::*', $dom->documentElement) as $xmlns) {
            if (in_array($xmlns->nodeValue, $known)) {
                continue;
            }

            $domNodeList = $dom->getElementsByTagNameNS($xmlns->nodeValue, '*');
            for ($i = $domNodeList->length; --$i >= 0;) {
                $element = $domNodeList->item($i);
                $element->parentNode->removeChild($element);
            }
        }

        // Importing schemas.
        $imports = '';
        foreach ($schemaLocations as $namespace => $location) {
            $imports .= sprintf('<xsd:import namespace="%s" schemaLocation="%s" />'."\n", $namespace, $location);
        }

        $source = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<xsd:schema xmlns="http://symfony.com/schema"
xmlns:xsd="http://www.w3.org/2001/XMLSchema"
targetNamespace="http://symfony.com/schema"
elementFormDefault="qualified">

    <xsd:import namespace="http://www.w3.org/XML/1998/namespace" />
    $imports
</xsd:schema>
EOF
        ;

        return @$dom->schemaValidateSource($source);
    }

    private function getDoctrineSchemePath(): string
    {
        static $path;

        if ($path) {
            return $path;
        }

        $reflector = new ReflectionClass('Doctrine\\ORM\\UnitOfWork');
        $path = realpath(dirname($reflector->getFileName()) . '/../../../doctrine-mapping.xsd');
        return $path;
    }
}
