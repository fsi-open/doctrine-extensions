<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Versionable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Versionable\Mapping\Annotation as Versionable;

/**
 * @ORM\Entity
 * @Versionable\Versionable(mappedBy="versions", strategy="FSi\DoctrineExtensions\Versionable\Strategy\ArchiveStrategy")
 */
class ArchivableArticle
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Versionable\Version
     * @ORM\Column(type="integer", nullable=true)
     * @var integer
     */
    private $publishedVersion;

    /**
     * @Versionable\Versionable
     * @var integer
     */
    private $version;

    /**
     * @Versionable\Versionable
     * @var integer
     */
    private $status;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $date;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $title;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $contents;

    /**
     * @ORM\OneToMany(targetEntity="ArchivableArticleVersion", mappedBy="article", indexBy="version")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $versions;

    public function __construct()
    {
        $this->versions = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function setPublishedVersion($version = null)
    {
        $this->publishedVersion = $version;
        return $this;
    }

    public function getPublishedVersion()
    {
        return $this->publishedVersion;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version = null)
    {
        $this->version = $version;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status = null)
    {
        $this->status = (int)$status;
    }

    public function getVersions()
    {
        return $this->versions;
    }
}
