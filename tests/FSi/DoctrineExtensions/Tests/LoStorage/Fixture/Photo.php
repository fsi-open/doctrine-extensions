<?php

namespace FSi\DoctrineExtensions\Tests\LoStorage\Fixture;

use FSi\DoctrineExtensions\LoStorage\Entity\AbstractStorage;
use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\LoStorage\Mapping\Annotation as LO;

/**
 * @ORM\Entity
 */
class Photo extends AbstractStorage
{
}
