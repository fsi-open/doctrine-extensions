<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractXmlDriver;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata;
use RuntimeException;

class Xml extends AbstractXmlDriver
{
    const LOCALE = 'translatable-locale';
    const FIELD_TRANSLATION = 'translatable-field';

    /**
     * {@inheritdoc}
     */
    protected function loadExtendedClassMetadata(ClassMetadataInfo $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        if (!($extendedClassMetadata instanceof ClassMetadata)) {
            throw new RuntimeException(sprintf(
                'Expected metadata of class "%s", got "%s"',
                '\FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata',
                get_class($extendedClassMetadata)
            ));
        }

        $mapping = $this->getFileMapping($extendedClassMetadata);
        // First iterate over nodes of translated entities, which are not part
        // of the Doctrine fields group
        $fsiMapping = $mapping->children(self::FSI_NAMESPACE_URI);
        foreach ($fsiMapping as $translatableMapping) {
            if ($translatableMapping->getName() === self::LOCALE) {
                $extendedClassMetadata->localeProperty = $this->getAttribute($translatableMapping, 'field');
            }

            if ($translatableMapping->getName() === self::FIELD_TRANSLATION) {
                $extendedClassMetadata->addTranslatableProperty(
                    $this->getAttribute($translatableMapping, 'mappedBy'),
                    $this->getAttribute($translatableMapping, 'field'),
                    $this->getAttribute($translatableMapping, 'targetField')
                );
            }
        }

        // Then iterate over fields - this is only to set the locale of the
        // translation entity
        if (count($fsiMapping) || !isset($mapping->field)) {
            return;
        }

        foreach ($mapping->field as $fieldMapping) {
            $translationMapping = $fieldMapping->children(self::FSI_NAMESPACE_URI);
            if (isset($translationMapping->{self::LOCALE})) {
                $extendedClassMetadata->localeProperty = $this->getAttribute($fieldMapping, 'name');
            }
        }
    }
}
