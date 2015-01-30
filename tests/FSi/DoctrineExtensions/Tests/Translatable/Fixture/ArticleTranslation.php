<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation as Uploadable;

/**
 * @ORM\Entity
 */
class ArticleTranslation
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Translatable\Locale
     * @ORM\Column(type="string", length=2)
     * @var string
     */
    private $locale;

    /**
     * @ORM\Column
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $introduction;

    /**
     * @ORM\Column
     * @var string
     */
    private $contents;

    /**
     * @var string
     *
     * @ORM\Column(name="intro_image_path", type="string", length=255, nullable=true)
     * @Uploadable\Uploadable(targetField="introImage")
     */
    protected $introImagePath;

    /**
     * @var \SplFileInfo|\FSi\DoctrineExtensions\Uploadable\File
     */
    protected $introImage;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="translations")
     * @ORM\JoinColumn(name="article", referencedColumnName="id")
     * @var \FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article
     */
    private $article;

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getIntroduction()
    {
        return $this->introduction;
    }

    /**
     * @param string $introduction
     */
    public function setIntroduction($introduction)
    {
        $this->introduction = $introduction;
    }

    public function setContents($contents)
    {
        $this->contents = $contents;
        return $this;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function setLocale($locale)
    {
        $this->locale = (string) $locale;
        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

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
