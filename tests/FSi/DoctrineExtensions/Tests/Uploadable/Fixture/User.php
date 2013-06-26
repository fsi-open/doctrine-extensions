<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation\Uploadable;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class User
{
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     */
    public $name = 'chuck testa';

    /**
     * @var string
     *
     * @ORM\Column(length=255, nullable=true)
     * @Uploadable(targetField="file", domain="one")
     */
    protected $fileKey;

    /**
     * @var mixed
     */
    protected $file;

    /**
     * @var string
     *
     * @ORM\Column(length=255, nullable=true)
     * @Uploadable(targetField="file2", domain="two")
     */
    protected $file2Key;

    /**
     * @var mixed
     */
    protected $file2;

    public function getId()
    {
        return $this->id;
    }

    public function setFile($file)
    {
        if (!empty($file)) {
            $this->file = $file;
        }
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getFileKey()
    {
        return $this->fileKey;
    }

    public function deleteFile()
    {
        $this->file = null;
    }

    public function setFile2($file)
    {
        if (!empty($file)) {
            $this->file2 = $file;
        }
    }

    public function getFile2()
    {
        return $this->file2;
    }

    public function getFile2Key()
    {
        return $this->file2Key;
    }

    public function deleteFile2()
    {
        $this->file2 = null;
    }

}
