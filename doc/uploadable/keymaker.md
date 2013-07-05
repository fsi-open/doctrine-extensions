# Uploadable - Uploadable behavioral extension for Doctrine 2

## Keymaker

Uploadable comes with default keymaker.

```php
<?php

use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;

$keymaker = new Entity();
```

You can use it as default keymaker (as described in uploadable documentation), or explicitely per field. Either way you can always set `pattern` for keymaker:

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

Specified `keyPattern` will be passed to keymaker during key generation, so you can control that process.

Some variables will be replaced:
* {fqcn} - Fully qualified class name **without** slashes, so `Acme\DemoBundle\Bundle\Entity\User` will become `AcmeDemoBundleBundleEntityUser`.
* {property} - Property name, in example above it would be `field` and `field2`.
* {id} - Identity of entity.
* {original_name} - Name of file.

Others variables as well as consts (numbers, slashes etc) will be ignored.

Default `keyPattern` for `FSi\DoctrineExtensions\Uploadable\Keymaker\Entity` is `/{fqcn}/{property}/{id}/{original_name}`.

**Heads up!** With keyPattern it's easy to make situation when uploadable extension can't generate unique key, so please be carefull with this option.
