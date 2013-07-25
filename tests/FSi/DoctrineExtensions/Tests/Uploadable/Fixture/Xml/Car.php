<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml;

class Car
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

    public function getId()
    {
        return $this->id;
    }

    public function setFileKey($key)
    {
        $this->fileKey = $key;
    }

    public function getFileKey()
    {
        return $this->fileKey;
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

    public function deleteFile()
    {
        $this->file = null;
    }
}
