<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="promotion")
 * @ORM\Entity()
 */
class Promotion extends ExcerptContentPage
{
}
