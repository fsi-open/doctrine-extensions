# Uploadable - Uploadable behavioral extension for Doctrine 2

## Xml config example

Entity:

```php
<?php

namespace Acme\DemoBundle\Entity

class User
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    protected $fileKey;

    /**
     * @var mixed
     */
    protected $file;
}
```

Xml:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns:fsi="http://fsi.pl/schemas/orm/doctrine-extensions-mapping"
    xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
    http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="Acme\DemoBundle\Entity\User">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>
        <field type="string" name="name" length="255"/>
        <field type="string" name="fileKey" nullable="true">
            <fsi:uploadable targetField="file" filesystem="filesystem1"/>
        </field>
    </entity>

</doctrine-mapping>
```
