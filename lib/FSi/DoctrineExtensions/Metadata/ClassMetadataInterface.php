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

interface ClassMetadataInterface
{
    public function getClassName(): string;

    public function setClassName(string $name): void;

    public function getClassReflection(): ReflectionClass;
}
