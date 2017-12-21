<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance;

use Doctrine\ORM\Mapping as ORM;
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

    public function setExcerpt(?string $excerpt): void
    {
        $this->excerpt = $excerpt;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function getCoverImagePath(): ?string
    {
        return $this->coverImagePath;
    }

    public function setCoverImagePath(?string $coverImagePath): void
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
