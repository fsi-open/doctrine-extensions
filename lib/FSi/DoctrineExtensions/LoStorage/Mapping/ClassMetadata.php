<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\LoStorage\Mapping;

use FSi\Component\Metadata\AbstractClassMetadata;

class ClassMetadata extends AbstractClassMetadata
{
    /**
     * @var string
     */
    public $filepath;

    /**
     * @var string
     */
    public $storageProperty;

    /**
     * @var array
     */
    protected $largeObjects = array();

    /**
     * Add mapping for new large object.
     *
     * @param string $property
     * @param string $targetProperty
     */
    public function addLargeObject($name, $fields, $values = array())
    {
        $this->largeObjects[$name] = array(
            'fields' => $fields,
            'values' => $values
        );
    }

    /**
     * Returns true if associated class has any embedded large objects.
     *
     * @return boolean
     */
    public function hasLargeObjects()
    {
        return !empty($this->largeObjects);
    }

    /**
     * Returns metadata for large object specified by name
     *
     * @return array
     */
    public function getLargeObject($name)
    {
        return $this->largeObjects[$name];
    }

    /**
     * Returns array of all embedded large objects metadata indexed by large objects' name
     *
     * @return array
     */
    public function getLargeObjects()
    {
        return $this->largeObjects;
    }
}
