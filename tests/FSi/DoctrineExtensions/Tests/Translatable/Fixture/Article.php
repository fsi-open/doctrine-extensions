<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Traits\SubtitleTrait;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;
use FSi\DoctrineExtensions\Uploadable\File;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation as Uploadable;
use SplFileInfo;

/**
 * @ORM\Entity(repositoryClass="FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository")
 */
class Article
{
    use SubtitleTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var string
     *
     * @ORM\Column(name="image_path", nullable=true)
     * @Uploadable\Uploadable(targetField="image")
     */
    private $imagePath;

    /**
     * @var SplFileInfo|File
     */
    private $image;

    /**
     * @var Section
     *
     * @ORM\ManyToOne(targetEntity="Section", inversedBy="articles")
     */
    private $section;

    /**
     * @var Collection|Comment[]
     * @Translatable\Translatable(mappedBy="translations")
     */
    private $comments;

    /**
     * @var Collection|Comment[]
     * @Translatable\Translatable(mappedBy="translations")
     */
    private $specialComments;

    /**
     * @ORM\OneToMany(targetEntity="ArticleTranslation", mappedBy="article", indexBy="locale")
     * @var Collection
     */
    private $translations;

    /**
     * @ORM\ManyToMany(targetEntity="Category", inversedBy="articles")
     * @ORM\JoinTable(name="article2category",
     *      joinColumns={@ORM\JoinColumn(name="category", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="article", referencedColumnName="id")}
     * )
     * @var Collection
     */
    private $categories;

    /**
     * @var Collection|ArticlePage[]
     * @Translatable\Translatable(mappedBy="translations")
     */
    private $pages;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->pages = new ArrayCollection();
        $this->specialComments = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeaser(): ?string
    {
        return $this->teaser;
    }

    public function setTeaser(?string $teaser): void
    {
        $this->teaser = $teaser;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setContents(?string $contents): void
    {
        $this->contents = $contents;
    }

    public function getContents(): ?string
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

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function setImagePath(string $imagePath): void
    {
        $this->imagePath = $imagePath;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image): void
    {
        $this->image = $image;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return (string) $this->locale;
    }

    public function addCategory(Category $category): void
    {
        $this->categories[] = $category;
        $category->addArticle($this);
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getSection(): ?Section
    {
        return $this->section;
    }

    public function setSection(?Section $section): void
    {
        $this->section = $section;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): void
    {
        $this->comments->add($comment);
    }

    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
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

    public function getPages(): Collection
    {
        return $this->pages;
    }

    public function addPage(ArticlePage $page): void
    {
        $this->pages->add($page);
    }

    public function removePage(ArticlePage $page): void
    {
        $this->pages->removeElement($page);
    }
}
