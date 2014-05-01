<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="event")
 * @ORM\Entity()
 */
class Event extends ExcerptContentPage
{
}
