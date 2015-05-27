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
class Comment
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column
     */
    private $content;

    /**
     * @var Article
     *
     * @ORM\ManyToOne(targetEntity="ArticleTranslation", inversedBy="comments")
     */
    private $articleTranslation;

    /**
     * @param \FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article $articleTranslation
     */
    public function setArticleTranslation($articleTranslation)
    {
        $this->articleTranslation = $articleTranslation;
    }

    /**
     * @return \FSi\DoctrineExtensions\Tests\Translatable\Fixture\Article
     */
    public function getArticleTranslation()
    {
        return $this->articleTranslation;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

}
