<?php

namespace FSi\DoctrineExtension\Tests\LoStorage\Fixture;

use FSi\DoctrineExtension\LoStorage\Entity\AbstractStorage;
use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtension\LoStorage\Mapping\Annotation as LO;

/**
 * @ORM\Entity
 */
class Photo extends AbstractStorage
{
}
