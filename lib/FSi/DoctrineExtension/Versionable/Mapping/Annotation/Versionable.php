<?php

namespace FSi\DoctrineExtension\Versionable\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Versionable extends Annotation
{
    public $mappedBy;
    public $strategy = 'FSi\DoctrineExtension\Versionable\Strategy\SimpleStrategy';
    public $targetField;
}
