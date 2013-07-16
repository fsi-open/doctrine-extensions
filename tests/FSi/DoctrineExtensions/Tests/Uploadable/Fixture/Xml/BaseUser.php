<?php

namespace FSi\DoctrineExtensions\Tests\Uploadable\Fixture\Xml;

/**
 * This class exists only to make it possible to complete test.
 */
class BaseUser
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
}
