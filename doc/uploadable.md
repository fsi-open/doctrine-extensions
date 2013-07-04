# Uploadable - Uploadable behavioral extension for Doctrine 2

**Uploadable** will automate for you storing files as a field of entity.

Features:
- it uses Gaufrette in background (https://github.com/KnpLabs/Gaufrette) so you can use all of its adapters (local. amazon s3, opencloud and many more)
- multiple filesystems even in scope of one entity (so you can store each file in different filesystem)

## Creating and attaching the UploadableListener to the event manager

### Filesystems

First argument must be an array with filesystems, where keys are later identifiers of filesystems. Each filesystem must be instance of `Gaufrette\Filesystem`.

```php
<?php

use Gaufrette\Filesystem;
use Gaufrette\Adapter;

$filesystem1 = new Filesystem(new Adapter\Local('/some/path'));
$filesystem2 = new Filesystem(new Adapter\Ftp('/other/path', 'example.com'));

$filesystems = array(
    'filesystem1' => $filesystem1,
    'filesystem2' => $filesystem2,
);
```

### FileHandler

Second argument defines class, that will handle conversion of anything you put as file into instance of `FSi\DoctrineExtensions\Uploadable\File`.

You can define default file handler as:

```php
<?php

use FSi\DoctrineExtensions\Uploadable\FileHandler;

$handler = new FileHandler\ChainHandler(array(
    new FileHandler\GaufretteHandler(),
    new FileHandler\SplFileInfoHandler(),
));

```

This configuration will allow you to handle instances of `Gaufrette\File` (including `FSi\DoctrineExtensions\Uploadable\File`) and `SplFileInfo`.

File handler must be instance of `FSi\DoctrineExtensions\Uploadable\FileHandler\FileHandlerInterface`.

### Result

```php

use Doctrine\Common\EventManager;
use FSi\DoctrineExtensions\Uploadable\UploadableListener
use FSi\DoctrineExtensions\Uploadable\Keymaker\Entity;

$evm = new EventManager();

$uploadableListener = new UploadableListener($filesystems, $fileHandler, $keymaker);
$evm->addEventSubscriber($uploadableListener);
// now this event manager should be passed to entity manager constructor

// It's good idea to set default filesystem if you want to use annotations without
// specifying filesystem.
$uploadableListener->setDefaultFilesystem($filesystem1);

// It's also good idea to set default keymaker if you want to use annotations without
// specifying keymaker.
$keymaker = new Entity();
$uploadableListener->setDefaultKeymaker($keymaker);
// It must be instance of FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface.

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
	 * @Uploadable(targetField="file", filesystem="filesystem1")
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
- **filesystem** - Filesystem name. If not set, default filesystem will be chosen.
- **keymaker** - Strategy for creating file keys, null by default. Instance of `FSi\DoctrineExtensions\Uploadable\Keymaker\KeymakerInterface`
- **keyLength** - Allowed key length, 255 by default.
- **keyPattern** - Pattern of key. Depend on keymaker, can contain replaceable variables.

## Usage

```php
<?php

use Acme\DemoBundle\Entity\User; // See declaration above.

// Must be instances of something, that $fileHandler can handle.
$file;
$file2;

// Insertion.
$user = new User();
$user->setFile($file);

$entityManager->persist($user);
$entityManager->flush();

$user->getFileKey(); // Returns new key of file.
$user->getFile()->getKey(); // Returns the same as line above, but you must check if getFile doesn't return null.
$tmpFile1 = $user->getFile(); // Instance of FSi\DoctrineExtensions\Uploadable\File.
$tmpFile1->exists(); // true

// Update.
$user->setFile($file2);
$entityManager->flush();
$tmpFile1->exists(); // false;

$tmpFile2 = $user->getFile();

// Deletion.
$entityManager->remove($user);
$entityManager->flush();

$tmpFile2->exists(); // false

```

## Update of content

If file is already attached, you can modify file directly.

```php
<?php

$user->getFile()->setContent('some content');
```

**Heads up!** This way if something fails **changes won't be undone!** Remember that replacing it with something new is much more safer.

## Deletion and update

After update/delete old files will be *automatically* removed from adequate filesystems.

## Deletion and update when Doctrine update fails

If you update/delete file and flush will raise an exception, old files will still be present.

```php
<?php

try {
    $file1 = $user->getFile1();
    $user->deleteFile1();
    $file2 = $user->getFile2();
    $user->setFile2($someFile);

    $entityManager->flush(); // Exception.
} catch (Exception $e) {
    $file1->exists(); // true
    $file2->exists(); // true
}

```

**Heads up!** Usually new files (in case of update) **will** be created, so after such case (like above) there is big chance
that new files exists, but aren't bounded to any entity through key.

### When preserving old files doesn't work

If you turn of auto commit, **it is up to you to backup old files!**

```php
<?php

$entityManager->getConnection()->beginTransaction(); // Suspend auto-commit.

try {
    $file1 = $user->getFile1();
    $user->deleteFile1();
    $file2 = $user->getFile2();
    $user->setFile2($someFile);

    $entityManager->flush(); // Exception.
    $entityManager->getConnection()->commit();
} catch (Exception $e) {
    $entityManager->getConnection()->rollback();
    $entityManager->close();
    $file1->exists(); // false
    $file2->exists(); // false
}

```
