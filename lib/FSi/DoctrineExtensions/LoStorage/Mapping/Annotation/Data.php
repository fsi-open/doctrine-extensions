<?php

namespace FSi\DoctrineExtensions\LoStorage\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class Data extends Annotation
{
	public $lo = 'lo';
}
