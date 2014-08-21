<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;

/**
 * @ORM\Entity
 */
class TranslatableWithPersistentLocaleTranslation
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
     * @ORM\Column
     * @var string
     */
    private $contents;

    /**
     * @ORM\ManyToOne(targetEntity="\FSi\DoctrineExtensions\Tests\Translatable\Fixture\TranslatableWithPersistentLocale", inversedBy="translations")
     * @ORM\JoinColumn(name="translatable", referencedColumnName="id")
     */
    private $translatable;

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
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

    public function setLocale($language)
    {
        $this->language = (string)$language;
        return $this;
    }

    public function getLocale()
    {
        return $this->language;
    }

}
