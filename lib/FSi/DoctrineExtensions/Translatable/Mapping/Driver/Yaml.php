<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable\Mapping\Driver;

use FSi\DoctrineExtensions\Mapping\Driver\AbstractYamlDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;

class Yaml extends AbstractYamlDriver
{
    const LOCALE = 'locale';

    /**
     * {@inheritdoc}
     */
    protected function loadExtendedClassMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        $mapping = $this->getFileMapping($extendedClassMetadata);
        if (!isset($mapping['type'])) {
            return;
        }

        // First iterate over nodes of translated entities, which are not part
        // of the Doctrine fields group
        if (isset($mapping['fsi']) && isset($mapping['fsi']['translatable'])) {
            if (!is_array($mapping['fsi']['translatable'])) {
                throw new MappingException(sprintf(
                    'Key "translatable" should be an array, is "%s" for "%s" entity.',
                    gettype($mapping['fsi']['translatable']),
                    $extendedClassMetadata->getClassName()
                ));
            }

            $locale = isset($mapping['fsi']['translatable'][self::LOCALE])
                ? $mapping['fsi']['translatable'][self::LOCALE]
                : self::LOCALE
            ;
            $extendedClassMetadata->localeProperty = $locale;

            if (!isset($mapping['fsi']['translatable']['fields'])) {
                return;
            }

            if (!is_array($mapping['fsi']['translatable']['fields'])) {
                throw new MappingException(sprintf(
                    'Key "fields" in group "translatable" should be an array, is "%s" for "%s" entity.',
                    gettype($mapping['fsi']['translatable']),
                    $extendedClassMetadata->getClassName()
                ));
            }

            foreach ($mapping['fsi']['translatable']['fields'] as $field => $options) {
                $extendedClassMetadata->addTranslatableProperty(
                    $this->getValue($options, 'mappedBy'),
                    $field,
                    $this->getValue($options, 'targetField')
                );
            }
        }

        // Then iterate over fields - this is only to set the locale of the
        // translation entity
        if (!isset($mapping['fields']) || !is_array($mapping['fields'])) {
            return;
        }

        foreach ($mapping['fields'] as $field => $config) {
            if (!isset($config['fsi'])) {
                continue;
            }

            if (!is_array($config['fsi'])) {
                throw new MappingException(sprintf(
                    'Key "fsi" should be an array, is "%s" for field "%s" of "%s" entity.',
                    gettype($mapping['fsi']['translatable']),
                    $field,
                    $extendedClassMetadata->getClassName()
                ));
            }
            if (!isset($config['fsi']['translatable'])) {
                continue;
            }
            if (!is_array($config['fsi']['translatable'])) {
                throw new MappingException(sprintf(
                    'Key "translatable" should be an array, is "%s" for "%s" entity.',
                    gettype($mapping['fsi']['translatable']),
                    $extendedClassMetadata->getClassName()
                ));
            }

            $translatable = $config['fsi']['translatable'];
            $locale = isset($translatable[self::LOCALE])
                ? $translatable[self::LOCALE]
                : self::LOCALE
            ;
            $extendedClassMetadata->localeProperty = $locale;
        }
    }

    /**
     * @param array $array
     * @return mixed
     */
    private function getValue(array $array, $key)
    {
        return isset($array[$key]) ? $array[$key] : null;
    }
}
