<?php

namespace FSi\DoctrineExtensions\Tests\LoStorage\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\LoStorage\Mapping\Annotation as LO;

/**
 * @ORM\Entity
 * @LO\Filepath(value="article")
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\Column(nullable=true)
     */
    private $title = null;

    /**
     * @ORM\Column(type="decimal", length=18, scale=4, nullable=true)
     */
    private $price = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @LO\Timestamp
     * @var DateTime
     */
    private $bigphoto_timestamp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @LO\Filename
     * @var string
     */
    private $bigphoto_filename;

    /**
     * @LO\Filepath(value="bigphoto")
     * @var string
     */
    private $bigphoto_filepath;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @LO\Mimetype
     * @var string
     */
    private $bigphoto_mimetype;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @LO\Size
     * @var integer
     */
    private $bigphoto_size;

    /**
     * @ORM\OneToOne(targetEntity="FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Photo", cascade={"remove"})
     * @ORM\JoinColumn(name="bigphoto_data", referencedColumnName="id", onDelete="SET NULL")
     * @LO\Data
     * @var \FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Photo
     */
    private $bigphoto_data;

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = (string)$title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setPrice($price)
    {
        $this->price = (double)$price;
        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setBigphotoFilepath($bigphoto)
    {
        $this->bigphoto_filepath = $bigphoto;
        return $this;
    }

    public function getBigphotoFilepath()
    {
        return $this->bigphoto_filepath;
    }

    public function getBigphotoFilename()
    {
        return $this->bigphoto_filename;
    }

    public function getBigphotoTimestamp()
    {
        return $this->bigphoto_timestamp;
    }

    public function getBigphotoMimetype()
    {
        return $this->bigphoto_mimetype;
    }

    public function getBigphotoSize()
    {
        return $this->bigphoto_size;
    }

}
