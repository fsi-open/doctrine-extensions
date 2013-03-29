<?php

namespace FSi\DoctrineExtension\Translatable\Mapping;

use FSi\Component\Metadata\AbstractClassMetadata;

class ClassMetadata extends AbstractClassMetadata
{
    public $localeProperty;

    protected $translatableProperties = array();

    public function addTranslatableProperty($translationAssociation, $property, $targetProperty = null)
    {
        if (!isset($targetProperty))
            $targetProperty = $property;
        if (!isset($this->translatableProperties[$translationAssociation]))
            $this->translatableProperties[$translationAssociation] = array();
        $this->translatableProperties[$translationAssociation][$property] = $targetProperty;
    }

    public function hasTranslatableProperties()
    {
        return !empty($this->translatableProperties);
    }

    public function getTranslatableProperties()
    {
        return $this->translatableProperties;
    }
}
