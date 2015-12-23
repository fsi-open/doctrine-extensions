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
     * @param ObjectManager $objectManager
     * @param TranslationAssociationMetadata $associationMetadata
     * @param object $object
     */
    public function __construct(
        ClassMetadata $classMetadata,
        TranslationAssociationMetadata $associationMetadata
    ) {
        $this->classMetadata = $classMetadata;
        $this->associationMetadata = $associationMetadata;
    }

    /**
     * @return TranslationAssociationMetadata
     */
    public function getAssociationMetadata()
    {
        return $this->associationMetadata;
    }

    /**
     * @return Mapping\ClassMetadata
     */
    public function getTranslatableMetadata()
    {
        return $this->associationMetadata->getClassMetadata();
    }
}
