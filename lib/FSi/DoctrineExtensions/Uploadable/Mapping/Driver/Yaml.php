<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Uploadable\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractYamlDriver;
use FSi\DoctrineExtensions\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Uploadable\Exception\MappingException;
use FSi\DoctrineExtensions\Uploadable\Mapping\ClassMetadata;
use RuntimeException;

class Yaml extends AbstractYamlDriver
{
    /**
     * @param ClassMetadataInfo $baseClassMetadata
     * @param ClassMetadataInterface $extendedClassMetadata
     * @return void
     * @throws RuntimeException
     * @throws MappingException
     */
    protected function loadExtendedClassMetadata(
        ClassMetadataInfo $baseClassMetadata,
        ClassMetadataInterface $extendedClassMetadata
    ): void {
        if (!($extendedClassMetadata instanceof ClassMetadata)) {
            throw new RuntimeException(sprintf(
                'Expected metadata of class "%s", got "%s"',
                ClassMetadata::class,
                get_class($extendedClassMetadata)
            ));
        }

        $mapping = $this->getFileMapping($extendedClassMetadata);
        if (isset($mapping['type']) && isset($mapping['fields']) && is_array($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $config) {
                if (isset($config['fsi'])
                    && is_array($config['fsi'])
                    && isset($config['fsi']['uploadable'])
                ) {
                    $uploadable = $config['fsi']['uploadable'];
                    if (!is_array($uploadable)) {
                        throw new MappingException(sprintf(
                            'Wrong "uploadable" format for "%s" field in "%s" entity.',
                            $field,
                            $extendedClassMetadata->getClassName()
                        ));
                    }

                    $keyLength = $uploadable['keyLength'] ?? null;
                    if (!is_null($keyLength)) {
                        $keyLength = (int) $keyLength;
                    }
                    $extendedClassMetadata->addUploadableProperty(
                        $field,
                        $uploadable['targetField'] ?? null,
                        $uploadable['filesystem'] ?? null,
                        $uploadable['keymaker'] ?? null,
                        $keyLength,
                        $uploadable['keyPattern'] ?? null
                    );
                }
            }
        }
    }
}
