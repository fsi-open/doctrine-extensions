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
 * @ORM\Table(name="promotion")
 * @ORM\Entity()
 */
class Promotion extends ExcerptContentPage
{
    /**
     * @var string
     *
     * @ORM\Column(name="intro_image_path", nullable=true)
     * @Uploadable\Uploadable(targetField="introImage")
     */
    protected $introImagePath;

    protected $introImage;

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
}
