<?php

namespace FSi\DoctrineExtension\Tests\Translatable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtension\Translatable\Mapping\Annotation as Translatable;

/**
 * @ORM\Entity
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var string
     */
    private $date;

    /**
     * @Translatable\Language
     * @var string
     */
    private $language;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $title;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $contents;

    /**
     * @ORM\OneToMany(targetEntity="ArticleTranslation", mappedBy="article", indexBy="language")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setTitle($title)
    {
        $this->title = (string)$title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContents($contents)
    {
        $this->contents = (string)$contents;
        return $this;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function setLanguage($language)
    {
        $this->language = (string)$language;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getTranslations()
    {
        return $this->translations;
    }
}
