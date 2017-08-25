<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Traits\SubtitleTrait;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;
use FSi\DoctrineExtensions\Uploadable\File;
use SplFileInfo;

/**
 * @ORM\Entity(repositoryClass="\FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository")
 */
class Article
{
    use SubtitleTrait;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var string
     */
    private $date;

    /**
     * @Translatable\Locale
     * @var string
     */
    private $locale;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $title;

    /**
     * @Translatable\Translatable(mappedBy="translations", targetField="introduction")
     * @var string
     */
    private $teaser;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $contents;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var SplFileInfo|File
     */
    private $introImage;

    /**
     * @var Section
     *
     * @ORM\ManyToOne(targetEntity="Section", inversedBy="articles")
     */
    private $section;

    /**
     * @var ArrayCollection|Comment[]
     * @Translatable\Translatable(mappedBy="translations")
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="ArticleTranslation", mappedBy="article", indexBy="locale")
     * @var ArrayCollection
     */
    private $translations;

    /**
     * @ORM\ManyToMany(targetEntity="Category", inversedBy="articles")
     * @ORM\JoinTable(name="article2category",
     *      joinColumns={@ORM\JoinColumn(name="category", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="article", referencedColumnName="id")}
     * )
     * @var ArrayCollection
     */
    private $categories;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * @param string $teaser
     */
    public function setTeaser($teaser)
    {
        $this->teaser = $teaser;
    }

    public function setDate(DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
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

    public function getIntroImage()
    {
        return $this->introImage;
    }

    public function setIntroImage($introImage)
    {
        $this->introImage = $introImage;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    public function getLocale()
    {
        return (string) $this->locale;
    }

    public function addCategory(Category $category)
    {
        $this->categories[] = $category;
        $category->addArticle($this);
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @return Section
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param Section $section
     */
    public function setSection($section)
    {
        $this->section = $section;
    }

    /**
     * @return ArrayCollection|Comment[]
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param Comment $comment
     */
    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
    }

    /**
     * @param Comment $comment
     */
    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
    }
}
