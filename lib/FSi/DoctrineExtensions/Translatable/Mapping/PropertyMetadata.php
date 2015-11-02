<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Translatable\Mapping;

use FSi\Component\Metadata\ClassMetadataInterface;

class PropertyMetadata
{
    /**
     * @var ClassMetadataInterface
     */
    protected $classMetadata;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $targetField;

    /**
     * @var bool
     */
    protected $copyFromDefault;

    /**
     * @param ClassMetadataInterface $classMetadata
     * @param string $name
     * @param string $targetField
     * @param bool $copyFromDefault
     */
    public function __construct(ClassMetadataInterface $classMetadata, $name, $targetField, $copyFromDefault)
    {
        $this->classMetadata = $classMetadata;
        $this->name = $name;
        $this->targetField = $targetField;
        $this->copyFromDefault = $copyFromDefault;
    }

    /**
     * @return ClassMetadataInterface
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @param ClassMetadataInterface $classMetadata
     */
    public function setClassMetadata($classMetadata)
    {
        $this->classMetadata = $classMetadata;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTargetField()
    {
        return $this->targetField;
    }

    /**
     * @param string $targetField
     */
    public function setTargetField($targetField)
    {
        $this->targetField = $targetField;
    }

    /**
     * @return boolean
     */
    public function isCopyFromDefault()
    {
        return $this->copyFromDefault;
    }

    /**
     * @param boolean $copyFromDefault
     */
    public function setCopyFromDefault($copyFromDefault)
    {
        $this->copyFromDefault = $copyFromDefault;
    }
}
