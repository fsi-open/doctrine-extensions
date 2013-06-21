<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Uploadable\Mapping;

use FSi\Component\Metadata\AbstractClassMetadata;

class ClassMetadata extends AbstractClassMetadata
{
    /**
     * @var array
     */
    protected $uploadableProperties = array();

    /**
     * Set specified property as uploadable.
     *
     * @param string $property
     * @param string $targetField
     * @param object $keymaker
     * @param integer $keyLength
     * @param string $domain
     */
    public function addUploadableProperty($property, $targetField, $domain = null, $keymaker = null, $keyLength = null)
    {
        $this->uploadableProperties[$property] = array('targetField' => $targetField, 'domain' => $domain, 'keymaker' => $keymaker, 'keyLength' => $keyLength);
    }

    /**
     * Returns true if associated class has any uploadable properties.
     *
     * @return boolean
     */
    public function hasUploadableProperties()
    {
        return !empty($this->uploadableProperties);
    }

    /**
     * Returns array of all uploadable properties indexed by property.
     *
     * @return array
     */
    public function getUploadableProperties()
    {
        return $this->uploadableProperties;
    }
}
