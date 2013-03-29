<?php

namespace FSi\DoctrineExtension\Translatable\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Translatable extends Annotation
{
    public $mappedBy;
    public $targetField;
}
