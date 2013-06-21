<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\Keymaker;

interface KeymakerInterface
{
    /**
     * Creates key for files.
     *
     * @param object $object
     * @param string $property
     * @param string $originalName
     * @param integer $keyLength
     * @return string
     */
    public function createKey($object, $property, $originalName, $keyLength);
}
