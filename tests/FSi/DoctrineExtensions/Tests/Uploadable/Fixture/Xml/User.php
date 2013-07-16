<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml;

class User
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name = 'chuck testa';

    /**
     * @var string
     */
    protected $fileKey;

    /**
     * @var mixed
     */
    protected $file;

    /**
     * @var string
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
