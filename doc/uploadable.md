# Uploadable - Uploadable behavioral extension for Doctrine 2

**Uploadable** will automate for you storing files as a field of entity.

Features:
- it uses Gaufrette in background (https://github.com/KnpLabs/Gaufrette) so you can use all of its adapters (local. amazon s3, opencloud and many more)
- multiple domains even in scope of one entity (so you can store each file in different domain)

## Creating and attaching the UploadableListener to the event manager

```php

use Doctrine\Common\EventManager;
use FSi\DoctrineExtensions\Uploadable\UploadableListener

$evm = new EventManager();
$uploadableListener = new UploadableListener(array(
    'filesystems' => array(/* (...) */), // See description below.
));
$evm->addEventSubscriber($uploadableListener);
// now this event manager should be passed to entity manager constructor
```

Options of `UploadableListener`:
- **filesystems** - Required. List of domains and filesystems. For details see below.
- **default** - Default filesystem domain name. If empty string, first filesystem will be chosen as default.
- **keymaker** - Strategy for creating file keys, null by default. Can be set explicitly per field. Instance of `FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface`
- **keyLength** - Allowed key length, 255 by default. Can be set explicitly per field.

## Filesystems

To make uploadable work, you must pass array of filesystems to `UploadableListener`. Each filesystem must be instance of `Gaufrette\Filesystem`.

```php

use Gaufrette\Filesystem;
use Gaufrette\Adapter;

$filesystem1 = new Filesystem(new Adapter\Local('/some/path'));
$filesystem2 = new Filesystem(new Adapter\Ftp('/other/path', 'example.com'));

$filesystems = array(
    'domain1' => $filesystem1,
    'domain2' => $filesystem2,
);

// And now you can initialize listener with these filesystems.
// $uploadableListener = new \FSi\DoctrineExtensions\Uploadable\UploadableListener(array('filesystems' => $filesystems));
```

## Simple entity annotations and usage example

Here is an example of an entity with uploadable property:

```php
<?php

namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Uploadable\Mapping\Annotation\Uploadable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class User
{
    /**
	 * @var integer
	 *
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(length=255)
	 */
	public $name;

	/**
	 * @ORM\Column(length=255, nullable=true)
	 * @Uploadable(targetField="file", domain="domain1")
	 */
	protected $fileKey;

	protected $file;

	public function setFile($file)
	{
	    if (!empty($file)) {
	        $this->file = $file;
	    }
	}

	public function getFile()
	{
	    return $this->file;
	}

	public function getFileKey()
	{
	    return $this->fileKey;
	}

	public function deleteFile()
	{
		$this->file = null;
	}
}
```

## Uploadable annotation options

- **targetField** - Required. Attribute of entity, the file object will be loaded to.
- **default** - Filesystem domain name. If not set, default filesystem will be chosen.
- **keymaker** - Strategy for creating file keys, null by default. Instance of `FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface`
- **keyLength** - Allowed key length, 255 by default.

## Files must be deleted from filesystems explicitly!

Since one file can be bounded to many fields and entities, files aren't deleted implicitly. It's up to you to take care of these files.

Please note, that file deletion (from filesystem) isn't part of Doctrine's transaction, so *if something go wrong, file will be irrevocably deleted*.

Still you can automate that process:

```php
<?php

class User
{
    // (...)

    public function deleteFile()
    {
        if ($this->file) {
            $this->file->delete();
        }
        $this->file = null;
    }

    // (...)
}

```

## Usage with `Symfony\Form`

If you intend to use this with `Symfony\Form`, construct `setFile` as you see above, otherwise you will lose file each time you
edit your entity (through form), if you're not updating file at the moment.

To use it, just remember to set `data_class` to `FSi\\DoctrineExtensions\\Uploadable\\File` and use standard `file` type:

```php
<?php

namespace Acme\DemoBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('file', 'file', array('required' => false, 'data_class' => 'FSi\\DoctrineExtensions\\Uploadable\\File'));
    }

    public function getName()
    {
        return 'user';
    }
}

```

It's up to you to make optional preview or any action, that would delete that file.
