<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @ORM\Column(type="integer")
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
     * @var Collection|Comment[]
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
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

    public function setContents(?string $contents): void
    {
        $this->contents = $contents;
    }

    public function getContents(): ?string
    {
        return $this->contents;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = (string) $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getIntroImagePath(): ?string
    {
        return $this->introImagePath;
    }

    public function setIntroImagePath(?string $introImagePath): void
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

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): void
    {
        $comment->setArticleTranslation($this);
        $this->comments->add($comment);
    }

    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
        $comment->setArticleTranslation(null);
    }

    public function getSpecialComments(): Collection
    {
        return $this->specialComments;
    }

    public function addSpecialComment(Comment $comment): void
    {
        $this->specialComments->add($comment);
    }

    public function removeSpecialComment(Comment $comment): void
    {
        $this->specialComments->removeElement($comment);
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): void
    {
        $this->article = $article;
    }

    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(ArticlePage $page): void
    {
        if (!$this->pages->contains($page)) {
            $this->pages->add($page);
        }
    }

    public function removePage(ArticlePage $page): void
    {
        $this->pages->removeElement($page);
    }
}
