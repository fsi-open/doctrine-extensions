<?php

namespace FSi\DoctrineExtensions\LoStorage\Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\LoStorage\Mapping\Annotation as LO;

/**
 * @ORM\MappedSuperClass
 */
abstract class AbstractStorage
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="blob")
     * @LO\Storage
     * @var string
     */
    protected $data;

    public function getId()
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
