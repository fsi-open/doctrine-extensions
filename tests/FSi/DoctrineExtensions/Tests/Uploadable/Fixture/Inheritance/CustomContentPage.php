<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Inheritance;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="page")
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="page_type", type="string")
 * @ORM\DiscriminatorMap({
 *      "promotion" = "Promotion",
 *      "event" = "Event",
 * })
 */
abstract class CustomContentPage
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="column1", type="text")
     */
    private $column1;

    /**
     * @var string
     *
     * @ORM\Column(name="column2", type="text")
     */
    private $column2;

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

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getColumn1()
    {
        return $this->column1;
    }

    /**
     * @param string $column1
     */
    public function setColumn1($column1)
    {
        $this->column1 = $column1;
    }

    /**
     * @return string
     */
    public function getColumn2()
    {
        return $this->column2;
    }

    /**
     * @param string $column2
     */
    public function setColumn2($column2)
    {
        $this->column2 = $column2;
    }
}
