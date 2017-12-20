<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Metadata;

use ReflectionClass;

abstract class AbstractClassMetadata implements ClassMetadataInterface
{
    /**
     * @var string
     */
    protected $class;

    public function __construct(string $class)
    {
        $this->setClassName($class);
    }

    /**
     * {@inheritdoc}
     */
    public function setClassName(string $class): void
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName(): string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassReflection(): ReflectionClass
    {
        return new ReflectionClass($this->getClassName());
    }
}
