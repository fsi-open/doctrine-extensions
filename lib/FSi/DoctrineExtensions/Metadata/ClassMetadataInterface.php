<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Metadata;

interface ClassMetadataInterface
{
    /**
     * @return string
     */
    public function getClassName();

    /**
     * @param string $name
     */
    public function setClassName($name);

    /**
     * @return \ReflectionClass
     */
    public function getClassReflection();
}
