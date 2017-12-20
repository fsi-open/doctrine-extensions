<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\DoctrineExtensions\Metadata;

class ClassMetadata extends AbstractClassMetadata
{
    /**
     * @var array
     */
    protected $classMetadata = [];

    /**
     * @var array
     */
    protected $propertyMetadata = [];

    /**
     * @var array
     */
    protected $methodMetadata = [];

    public function addClassMetadata(string $index, $value): void
    {
        $this->classMetadata[$index] = $value;
    }

    public function hasClassMetadata(string $index): bool
    {
        return isset($this->classMetadata[$index]);
    }

    public function getClassMetadata(string $index)
    {
        return $this->hasClassMetadata($index)
            ? $this->classMetadata[$index]
            : null
        ;
    }

    public function getAllClassMetadata(): array
    {
        return $this->classMetadata;
    }

    public function addPropertyMetadata(string $property, string $index, $value): void
    {
        if (!isset($this->propertyMetadata[$property])) {
            $this->propertyMetadata[$property] = [$index => $value];
        } else {
            $this->propertyMetadata[$property][$index] = $value;
        }
    }

    public function hasPropertyMetadata(string $property, string $index): bool
    {
        return isset(
            $this->propertyMetadata[$property],
            $this->propertyMetadata[$property][$index]
        );
    }

    public function getPropertyMetadata(string $property, string $index)
    {
        return $this->hasPropertyMetadata($property, $index)
            ? $this->propertyMetadata[$property][$index]
            : null
        ;
    }

    public function getAllPropertyMetadata(): array
    {
        return $this->propertyMetadata;
    }

    public function addMethodMetadata(string $method, string $index, $value): void
    {
        if (!isset($this->methodMetadata[$method])) {
            $this->methodMetadata[$method] = [$index => $value];
        } else {
            $this->methodMetadata[$method][$index] = $value;
        }
    }

    public function hasMethodMetadata(string $method, string $index): bool
    {
        return isset($this->methodMetadata[$method], $this->methodMetadata[$method][$index]);
    }

    public function getMethodMetadata(string $method, string $index)
    {
        if ($this->hasMethodMetadata($method, $index)) {
            return $this->methodMetadata[$method][$index];
        }

        return null;
    }

    public function getAllMethodMetadata(): array
    {
        return $this->methodMetadata;
    }
}
