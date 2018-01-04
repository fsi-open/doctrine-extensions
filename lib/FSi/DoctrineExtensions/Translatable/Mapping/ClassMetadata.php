<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Translatable\Mapping;

use FSi\DoctrineExtensions\Metadata\AbstractClassMetadata;

class ClassMetadata extends AbstractClassMetadata
{
    /**
     * @var string
     */
    public $localeProperty;

    /**
     * @var array
     */
    protected $translatableProperties = [];

    /**
     * Add specified property as translatable. The real translation is stored in
     * $targetField inside $translationAssociation.
     *
     * @param string $translationAssociation
     * @param string $property
     * @param string|null $targetField
     */
    public function addTranslatableProperty(
        string $translationAssociation,
        string $property,
        ?string $targetField = null
    ) {
        if (!isset($targetField)) {
            $targetField = $property;
        }
        if (!isset($this->translatableProperties[$translationAssociation])) {
            $this->translatableProperties[$translationAssociation] = [];
        }
        $this->translatableProperties[$translationAssociation][$property] = $targetField;
    }

    public function hasTranslatableProperties(): bool
    {
        return !empty($this->translatableProperties);
    }

    /**
     * Returns array of all translatable properties indexed by translation
     * association name and then by property name.
     *
     * @return array
     */
    public function getTranslatableProperties(): array
    {
        return $this->translatableProperties;
    }

    /**
     * @return TranslationAssociationMetadata[]
     */
    public function getTranslationAssociationMetadatas(): array
    {
        $metadatas = [];

        foreach ($this->getTranslatableProperties() as $association => $properties) {
            $metadatas[] = new TranslationAssociationMetadata($this, $association, $properties);
        }

        return $metadatas;
    }
}
