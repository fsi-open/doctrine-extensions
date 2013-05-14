<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Versionable\Mapping;

use FSi\Component\Metadata\AbstractClassMetadata;

class ClassMetadata extends AbstractClassMetadata
{
    /**
     * @var string
     */
    public $versionProperty;

    /**
     * @var string
     */
    public $versionAssociation;

    /**
     * @var string
     */
    public $statusProperty;

    /**
     * @var string
     */
    public $strategy;

    /**
     * @var array
     */
    protected $versionableProperties = array();

    /**
     * Add specified property as versionable. The real value is stored in $targetField inside
     * $versionAssociation.
     *
     * @param string $property
     * @param string $targetProperty
     */
    public function addVersionableProperty($property, $targetField = null)
    {
        if (!isset($targetField))
            $targetField = $property;
        $this->versionableProperties[$property] = $targetField;
    }

    /**
     * Returns true if associated class has any versionable properties.
     *
     * @return boolean
     */
    public function hasVersionableProperties()
    {
        return !empty($this->versionableProperties);
    }

    /**
     * Returns array of all versionable properties indexed by property name
     *
     * @return array
     */
    public function getVersionableProperties()
    {
        return $this->versionableProperties;
    }
}
