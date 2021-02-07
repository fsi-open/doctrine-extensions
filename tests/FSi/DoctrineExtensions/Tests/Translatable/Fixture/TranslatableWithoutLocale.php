<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;

/**
 * @ORM\Entity(repositoryClass="\FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository")
 */
class TranslatableWithoutLocale
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $contents;

    /**
     * @ORM\OneToMany(targetEntity="TranslatableWithoutLocaleTranslation", mappedBy="article", indexBy="locale")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $translations;

    /**
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setContents(?string $contents): void
    {
        $this->contents = $contents;
    }

    public function getContents(): ?string
    {
        return $this->contents;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }
}
