<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Traits\SubtitleTranslationTrait;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;
use FSi\DoctrineExtensions\Uploadable\File;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation as Uploadable;
use SplFileInfo;

/**
 * @ORM\Entity
 */
class ArticleTranslation
{
    use SubtitleTranslationTrait;

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
     * @var SplFileInfo|File
     */
    protected $introImage;

    /**
     * @var ArrayCollection|Comment[]
     *
     * @ORM\OneToMany(
     *      targetEntity="Comment",
     *      mappedBy="articleTranslation",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     */
    private $comments;

    /**
     * @var Collection|Comment[]
     *
     * @ORM\ManyToMany(targetEntity="Comment", cascade={"persist"})
     */
    private $specialComments;

    /**
     * @var Collection|ArticlePage[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="ArticlePage",
     *      mappedBy="articles",
     *      cascade={"persist"}
     * )
     */
    private $pages;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="translations")
     * @ORM\JoinColumn(name="article", referencedColumnName="id")
     * @var Article
     */
    private $article;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->specialComments = new ArrayCollection();
        $this->pages = new ArrayCollection();
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
        $comment->setArticleTranslation($this);
        $this->comments->add($comment);
    }

    /**
     * @param Comment $comment
     */
    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
        $comment->setArticleTranslation(null);
    }

    /**
     * @return ArrayCollection|Comment[]
     */
    public function getSpecialComments()
    {
        return $this->specialComments;
    }

    /**
     * @param Comment $comment
     */
    public function addSpecialComment(Comment $comment)
    {
        $this->specialComments->add($comment);
    }

    /**
     * @param Comment $comment
     */
    public function removeSpecialComment(Comment $comment)
    {
        $this->specialComments->removeElement($comment);
    }

    /**
     * @return Article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param Article $article
     */
    public function setArticle($article)
    {
        $this->article = $article;
    }

    /**
     * @return Collection|ArticlePage[]
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param ArticlePage $page
     */
    public function addPage(ArticlePage $page)
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
        }
    }

    /**
     * @param ArticlePage $page
     */
    public function removePage(ArticlePage $page)
    {
        $this->pages->removeElement($page);
    }
}
