<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;

/**
 * @ORM\Entity
 */
class TranslatableWithoutLocaleTranslation
{
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
    private $contents;

    /**
     * @ORM\ManyToOne(targetEntity="TranslatableWithoutLocale", inversedBy="translations")
     * @ORM\JoinColumn(name="translatable", referencedColumnName="id")
     * @var \FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithoutLocale
     */
    private $translatable;

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
}
