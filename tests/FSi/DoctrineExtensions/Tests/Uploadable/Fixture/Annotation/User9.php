<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Annotation;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation\Uploadable;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class User9
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(length=255, nullable=true)
     * @Uploadable(targetField="file", keymaker=@ORM\Column())
     */
    protected $fileKey;

    /**
     * @var mixed
     */
    protected $file;
}
