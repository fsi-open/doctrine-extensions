<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use FSi\DoctrineExtensions\Translatable\Mapping\TranslationAssociationMetadata;

/**
 * @internal
 */
class ClassTranslationContext
{
    /**
     * @var ClassMetadata
     */
    private $classMetadata;

    /**
     * @var TranslationAssociationMetadata
     */
    private $associationMetadata;

    /**
     * @var ClassMetadata
     */
    private $translationMetadata;

    /**
     * @param ClassMetadata $classMetadata
     * @param TranslationAssociationMetadata $associationMetadata
     * @param ClassMetadata $translationMetadata
     */
    public function __construct(
        ClassMetadata $classMetadata,
        TranslationAssociationMetadata $associationMetadata,
        ClassMetadata $translationMetadata
    ) {
        $this->classMetadata = $classMetadata;
        $this->associationMetadata = $associationMetadata;
        $this->translationMetadata = $translationMetadata;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    public function getTranslatableMetadata()
    {
        return $this->associationMetadata->getClassMetadata();
    }

    /**
     * @return TranslationAssociationMetadata
     */
    public function getAssociationMetadata()
    {
        return $this->associationMetadata;
    }

    /**
     * @return ClassMetadata
     */
    public function getTranslationMetadata()
    {
        return $this->translationMetadata;
    }
}
