<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Metadata;

abstract class AbstractClassMetadata implements ClassMetadataInterface
{
    /**
     * @var string
     */
    protected $class;

    public function __construct($class)
    {
        $this->setClassName($class);
    }

    /**
     * {@inheritdoc}
     */
    public function setClassName($class)
    {
        $this->class = (string) $class;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassReflection()
    {
        return new \ReflectionClass($this->getClassName());
    }
}
