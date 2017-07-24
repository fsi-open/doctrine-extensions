# Translatable - Translatable behavioral extension for Doctrine 2

## YAML config example

Translated entity:

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
     * @var \Acme\DemoBundle\Entity\UserTranslation[]
     */
    public $translations;
}
```

Translated entity's mapping:
```yaml
Acme\DemoBundle\Entity\User:
    type: entity
    id:
        id:
            type: integer
            generator:
                strategy: AUTO
    fsi:
        translatable:
            locale: locale # provide the field name mapped to locale
            fields:
                name:
                    mappedBy: translations
                    targetField: name
    oneToMany:
        translations:
            targetEntity: Acme\DemoBundle\Entity\UserTranslation
            mappedBy: user
            cascade: ["persist", "remove"]
            indexBy: locale
```


Translation entity:

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
     * @var \Acme\DemoBundle\Entity\User
     */
    public $user;
}
```

Translation entity's mapping:
```yaml
Acme\DemoBundle\Entity\UserTranslation:
    type: entity
    id:
        id:
            type: integer
            generator:
                strategy: AUTO
    fields:
        locale:
            type: string
            length: 2
            fsi:
                translatable:
                    locale: ~ # No value is needed here, field name is assigned as locale parameter identifier
        name:
            type: string
            length: 255
    manyToOne:
        user:
            targetEntity: Acme\DemoBundle\Entity\User
            inversedBy: translations
```

