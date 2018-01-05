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
class Category
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity="Article", mappedBy="categories")
     * @var Collection
     */
    private $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = (string)$title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function addArticle(Article $article): void
    {
        $this->articles[] = $article;
    }

    public function getArticles(): Collection
    {
        return $this->articles;
    }
}
