<?php

namespace FSi\DoctrineExtensions\Versionable\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Versionable extends Annotation
{
    public $mappedBy;
    public $strategy = 'FSi\DoctrineExtensions\Versionable\Strategy\SimpleStrategy';
    public $targetField;
}
