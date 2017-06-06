# Uploadable - Uploadable behavioral extension for Doctrine 2

## Keymaker

The Uploadable component comes a with default keymaker called `Entity`:

```php
<?php

use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;

$keymaker = new Entity();
```

You can use it either as the default keymaker (as described in uploadable documentation),
or explicitely per field. Should you want to, you can provide the `keyPattern`
parameter directly in the definition.

```php
<?php

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation\Uploadable;
use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;

class User
{
    // (...)

    /**
     * @ORM\Column()
     * @Uploadable(targetField="someField", keymaker=@Entity, keyPattern="/{fqcn}/{property}/{original_name}")
     */
    protected $field;

    /**
     * @ORM\Column()
     * @Uploadable(targetField="someField2", keyPattern="/some/directory/{fqcn}/{id}/{original_name}")
     */
    protected $field2;

    // (...)
}
```

Here's how the `/{fqcn}/{property}/{original_name}` pattern will be created:

* {fqcn} - is a fully qualified class name **without** slashes, so `Acme\DemoBundle\Bundle\Entity\User` will become `AcmeDemoBundleBundleEntityUser`.
* {property} - is the property name, in example above it would be `field` and `field2`.
* {id} - is the identity of entity.
* {original_name} - is the original name of the file.

Others variables, as well as constants (numbers, slashes etc), will be ignored.

The default `keyPattern` for `FSi\DoctrineExtensions\Uploadable\Keymaker\Entity` is `/{fqcn}/{property}/{id}/{original_name}`.

**Heads up!** With `keyPattern` it's easy to make situation when uploadable extension can't generate unique key, so please be carefull with this option.

## Transliterate Keymaker
A new keymaker has been added, `FSi\DoctrineExtensions\Uploadable\Keymaker\TransliterateEntity`,
 providing support for striping all problematic special characters, replacing spaces with '-', 
as well as translating non-lating characters to lowercase latin. It requires the 
[PHP Intl extension](http://php.net/manual/en/intl.installation.php) in order to function.
