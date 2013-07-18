# Uploadable - Uploadable behavioral extension for Doctrine 2

## Yaml config example

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

Yaml:
```yaml
Acme\DemoBundle\Entity\User:
    type: entity
    id:
        id:
            type: integer
            generator:
                strategy: AUTO
    fields:
        name:
            type: string
            length: 255
        fileKey:
            type: string
            length: 255
            nullable: true
            fsi:
                uploadable:
                    targetField: name
```
