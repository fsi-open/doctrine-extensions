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

/**
 * @ORM\Entity
 */
class ArticlePage
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $title;

    /**
     * @var Collection|ArticleTranslation[]
     *
     * @ORM\ManyToMany(targetEntity="ArticleTranslation", inversedBy="pages")
     */
    private $articles;

    public function __construct($title = null)
    {
        $this->articles = new ArrayCollection();
        $this->title = $title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(ArticleTranslation $article): void
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
        }
    }

    public function removeArticle(ArticleTranslation $article): void
    {
        $this->articles->remove($article);
    }
}
