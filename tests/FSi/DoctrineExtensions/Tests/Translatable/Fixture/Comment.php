<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable\Fixture;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Comment
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime
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

    public function __construct($content = null)
    {
        $this->date = new DateTime();
        if ($content) {
            $this->content = $content;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param ArticleTranslation $articleTranslation
     */
    public function setArticleTranslation($articleTranslation)
    {
        $this->articleTranslation = $articleTranslation;
    }

    /**
     * @return ArticleTranslation
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
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }
}
