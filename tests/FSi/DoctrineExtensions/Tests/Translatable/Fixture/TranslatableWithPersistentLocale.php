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
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;

/**
 * @ORM\Entity(repositoryClass="\FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository")
 */
class TranslatableWithPersistentLocale
{
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
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $contents;

    /**
     * @ORM\OneToMany(targetEntity="TranslatableWithPersistentLocaleTranslation", mappedBy="translatable", indexBy="locale")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
        $this->locale = $locale;
        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getTranslations()
    {
        return $this->translations;
    }
}
