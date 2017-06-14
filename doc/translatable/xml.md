# Translatable - Translatable behavioral extension for Doctrine 2

## XML config example

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
     * Acme\DemoBundle\UserTranslation[]
     */
    public $translations;
}
```

XML for the entity:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
    http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd"
    xmlns:fsi="http://fsi.pl/schemas/orm/doctrine-extensions-mapping">

    <entity name="Acme\DemoBundle\Entity\User"
            repository-class="FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository"
    >
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>
        <one-to-many field="translations" target-entity="Acme\DemoBundle\Entity\UserTranslation" mapped-by="user" index-by="locale" />

        <fsi:translatable-locale field="locale" /><!-- "field" is required to point at which property the mapping points -->
        <fsi:translatable-field field="name" mappedBy="translations" />
    </entity>

</doctrine-mapping>
```

Translation:

```php
<?php

namespace Acme\DemoBundle\Entity

class UserTranslation
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
     * @var Acme\DemoBundle\User
     */
    public $page;
}
```

XML for the translation:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
    http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd"
    xmlns:fsi="http://fsi.pl/schemas/orm/doctrine-extensions-mapping">

    <entity name="Acme\DemoBundle\Entity\UserTranslation">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>
        
        <field type="string" name="content" length="255"/>
        <field type="string" name="locale" length="2">
            <fsi:translatable-locale /><!-- No value needed, field name is used as locale property identifier -->
        </field>

        <many-to-one field="user" target-entity="cme\DemoBundle\Entity\User" inversed-by="translations" />
    </entity>

</doctrine-mapping>
```
