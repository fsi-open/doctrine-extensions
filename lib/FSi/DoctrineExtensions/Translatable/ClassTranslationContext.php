<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Translatable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use FSi\DoctrineExtensions\Translatable\Mapping\TranslationAssociationMetadata;
use FSi\DoctrineExtensions\Translatable\Model\TranslatableRepositoryInterface;
use FSi\DoctrineExtensions\Translatable\Mapping\ClassMetadata as TranslatableClassMetadata;

/**
 * @internal
 */
class ClassTranslationContext
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ClassMetadata
     */
    private $classMetadata;

    /**
     * @var TranslationAssociationMetadata
     */
    private $associationMetadata;

    public function __construct(
        ObjectManager $objectManager,
        ClassMetadata $classMetadata,
        TranslationAssociationMetadata $associationMetadata
    ) {
        $this->objectManager = $objectManager;
        $this->classMetadata = $classMetadata;
        $this->associationMetadata = $associationMetadata;
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }

    public function getTranslatableMetadata(): TranslatableClassMetadata
    {
        return $this->associationMetadata->getClassMetadata();
    }

    public function getAssociationMetadata(): TranslationAssociationMetadata
    {
        return $this->associationMetadata;
    }

    public function getTranslationMetadata(): ClassMetadata
    {
        $associationName = $this->associationMetadata->getAssociationName();
        $translationClass = $this->classMetadata->getAssociationTargetClass($associationName);
        return $this->objectManager->getClassMetadata($translationClass);
    }

    /**
     * @return TranslatableRepositoryInterface
     * @throws Exception\AnnotationException
     */
    public function getTranslatableRepository(): TranslatableRepositoryInterface
    {
        $repository = $this->objectManager->getRepository($this->classMetadata->getName());

        if (!($repository instanceof TranslatableRepositoryInterface)) {
            throw new Exception\AnnotationException(sprintf(
                'Entity "%s" has "%s" as its "repositoryClass" which does not implement "%s"',
                $this->classMetadata->getName(),
                get_class($repository),
                TranslatableRepositoryInterface::class
            ));
        }

        return $repository;
    }

    public function getObjectManager(): ObjectManager
    {
        return $this->objectManager;
    }
}
