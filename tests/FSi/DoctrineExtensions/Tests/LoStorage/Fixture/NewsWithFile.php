<?php

namespace FSi\DoctrineExtensions\Tests\LoStorage\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\LoStorage\Mapping\Annotation as LO;

/**
 * @ORM\Entity
 * @LO\Filepath(value="news_with_file")
 */
class NewsWithFile
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
     * @ORM\Column(nullable=true)
     */
    private $contents = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @LO\Timestamp(lo="thumb")
     * @var DateTime
     */
    private $thumbnail_timestamp;

    /**
     * @LO\Filename(lo="thumb", value="thumb.jpg")
     * @var string
     */
    private $thumbnail_filename;

    /**
     * @LO\File(lo="thumb")
     * @var \SplFileInfo
     */
    private $thumbnail_file;

    /**
     * @ORM\OneToOne(targetEntity="FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Photo", cascade={"remove"})
     * @ORM\JoinColumn(name="thumbnail_data", referencedColumnName="id", onDelete="SET NULL")
     * @LO\Data(lo="thumb")
     * @var \FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Photo
     */
    private $thumbnail_data;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @LO\Timestamp(lo="photo")
     * @var DateTime
     */
    private $photo_timestamp;

    /**
    * @LO\Filename(lo="photo", value="photo.jpg")
    * @var string
    */
    private $photo_filename;

    /**
    * @LO\File(lo="photo")
    * @var \SplFileInfo
    */
    private $photo_file;

    /**
    * @ORM\OneToOne(targetEntity="FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Photo", cascade={"remove"})
    * @ORM\JoinColumn(name="photo_data", referencedColumnName="id", onDelete="SET NULL")
    * @LO\Data(lo="photo")
    * @var \FSi\DoctrineExtensions\Tests\LoStorage\Fixture\Photo
    */
    private $photo_data;

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

    public function setContents($contents)
    {
        $this->contents = (string)$contents;
        return $this;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function setThumbnailFile($bigphoto)
    {
        $this->thumbnail_file = $bigphoto;
        return $this;
    }

    public function getThumbnailFile()
    {
        return $this->thumbnail_file;
    }

    public function getThumbnailTimestamp()
    {
        return $this->thumbnail_timestamp;
    }

    public function setPhotoFile($photo)
    {
        $this->photo_file = $photo;
        return $this;
    }

    public function getPhotoFile()
    {
        return $this->photo_file;
    }

    public function getPhotoTimestamp()
    {
        return $this->photo_timestamp;
    }

}
