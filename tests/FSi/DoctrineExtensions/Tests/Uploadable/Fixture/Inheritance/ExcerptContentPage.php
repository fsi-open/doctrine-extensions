<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\File;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation as Uploadable;

/**
 * @ORM\Table(name="page_excerpt")
 * @ORM\Entity()
 */
abstract class ExcerptContentPage extends CustomContentPage
{
    /**
     * @var string
     *
     * @ORM\Column(name="excerpt", type="text")
     */
    protected $excerpt;

    /**
     * @var string
     *
     * @ORM\Column(name="cover_image_path", type="string", length=255, nullable=true)
     * @Uploadable\Uploadable(targetField="coverImage")
     */
    protected $coverImagePath;

    protected $coverImage;

    /**
     * Set excerpt
     *
     * @param string $excerpt
     * @return Promotion
     */
    public function setExcerpt($excerpt)
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    /**
     * Get excerpt
     *
     * @return string
     */
    public function getExcerpt()
    {
        return $this->excerpt;
    }

    /**
     * @return string
     */
    public function getCoverImagePath()
    {
        return $this->coverImagePath;
    }

    /**
     * @param string $coverImagePath
     */
    public function setCoverImagePath($coverImagePath)
    {
        $this->coverImagePath = $coverImagePath;
    }

    public function getCoverImage()
    {
        return $this->coverImage;
    }

    public function setCoverImage($coverImage)
    {
        $this->coverImage = $coverImage;
    }
} 
