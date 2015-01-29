<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation as Uploadable;

/**
 * @ORM\Table(name="promotion")
 * @ORM\Entity()
 */
class Promotion extends ExcerptContentPage
{
    /**
     * @var string
     *
     * @ORM\Column(name="intro_image_path", type="string", length=255, nullable=true)
     * @Uploadable\Uploadable(targetField="introImage")
     */
    protected $introImagePath;

    protected $introImage;

    /**
     * @return string
     */
    public function getIntroImagePath()
    {
        return $this->introImagePath;
    }

    /**
     * @param string $introImagePath
     */
    public function setIntroImagePath($introImagePath)
    {
        $this->introImagePath = $introImagePath;
    }

    public function getIntroImage()
    {
        return $this->introImage;
    }

    public function setIntroImage($introImage)
    {
        $this->introImage = $introImage;
    }
}
