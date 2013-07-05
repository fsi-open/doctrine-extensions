# Large Objects' Storage behavioral extension for Doctrine 2

**Deprecated** - please use uploadable extension instead.

**LoStorage** behaviour will automate storing and retrieving large objects (BLOBs) in entities. It simplifies operation of
storing any file's contents in a BLOB field to setting its filepath (or URL) in specially annotated entity property which is
not persisted. Retrieving the BLOB's contents after loading an entity is as simple as referring to the cached file pointed by
filepath stored in the same special property.

Features:

- supports multiple BLOB fields per entity
- supports storing BLOB's timestamp in additional field in order to cache its contents into file only when it changes
- cached file can have original filename or always the same filename for specific BLOB field
- extension supports optionally storing file's mimetype and size in additional fields
- supports advanced cache operations on entity, class or database level
- only annotation mapping driver is now supported; yaml and xml may appear in the future

## Creating and attaching the LoStorageListener to the event manager

To attach the ``LoStorageListener`` to your event system:

```php
$evm = new \Doctrine\Common\EventManager();
$loStorageListener = new \FSi\DoctrineExtensions\LoStorage\LoStorageListener(array(
    'basePath' => '/path/to/temporary/directory'
));
$evm->addEventSubscriber($loStorageListener);
// now this event manager should be passed to entity manager constructor
```

LoStorageListener has a few useful options which can be passed to the constructor as an array or set later by specific methods:

- ``basePath`` (``string``) - the base directory where the whole LoStorage cache is being held, default: ``sys_get_temp_dir()``
- ``createMode`` (``integer``) - the permissions which are used during directory creation, default: ``0700``
- ``removeOrphans`` (``bool``) - flag determining if orphaned files will be searched and removed during every cache modification,
  default: ``false``
- ``identifierGlue`` (``string``) - string that is used to implode compound primary keys into directory name; it cannot contain
  character which is the directory separator in host's operating system, default: ``'-'``

The removing of orphaned files needs additional explanation. During the normal operation of LoStorage on a single application
instance (single machine) there is no possibility of any orphaned file (not associated with any Large Object in any entity) to
exist in LoStorage's cache directory. However, if your application has multiple instances on different servers which of them use
the same database, then it is possible that one of application's instances removes some Large Object and the other instances will
have its cache file left in the cache directory. The same situation could happen if you have Large Objects with variable
filenames and one application's instance change some Large Object's filename. If any of theses concerns your application you have
two options: turn on the "remove orphans" mode permanently or periodically fulfill the whole cache with this option turned on.
Which is the best solution depends on multiple factors such as average amount of entities per class or how frequent the Large
Objects are removed or their filenames change.

``LoStorageListener`` contains two additional public methods which are used to manipulate the cache contents.

- ``clearCache($objectManager, $class, $entity)`` - remove all cached files associated with specified entity, all entities of
  specified class or all entities managed by specified entity manager. after clearing the cache corresponding entities still
  contain filepaths but the files pointed by them no longer exists. it's developer responsibility to handle this situation.
- ``fillCache($objectManager, $class, $entity)`` - cache all Large Objects from specified entity, all entities of specified class
  or all entities managed by specified entity manager; this method can additionally remove orphaned files if this mode is set.

Beware that clearing the cache or its part (i.e. one entity's cache) will lead to a situation that some entities are already
loaded (during the cache clearing process) into entity manager but their BLOB fields are not cached in filesystem. An attempt to
use filepath to some filepath field in this situation will cause an error that file doesn't exists. So the best practice is to
call clear() on entity manager right after clearing the LoStorage's cache.

## Simple entity annotations example

Here is an example of using annotations to define large object storage in some simple entity.

```php
namespace Entity;

use FSi\DoctrineExtensions\LoStorage\Mapping\Annotation as LO;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class News
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(nullable=true)
     */
    private $title = null;

    /**
     * @ORM\Column(nullable=true)
     */
    private $contents = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @LO\Timestamp(lo="bigphoto")
     * @var DateTime
     */
    private $bigphoto_timestamp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @LO\Filename(lo="bigphoto")
     * @var string
     */
    private $bigphoto_filename;

    /**
     * @LO\Filepath(lo="bigphoto", value="news")
     * @var string
     */
    private $bigphoto_filepath;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @LO\Mimetype(lo="bigphoto")
     * @var string
     */
    private $bigphoto_mimetype;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @LO\Size(lo="bigphoto")
     * @var integer
     */
    private $bigphoto_size;

    /**
     * @ORM\OneToOne(targetEntity="Entity\Photo")
     * @ORM\JoinColumn(name="bigphoto_data", referencedColumnName="id", onDelete="SET NULL")
     * @LO\Data(lo="bigphoto")
     * @var Entity\Photo
     */
    private $bigphoto_data;

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = (string)$title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContents($contents)
    {
        $this->contents = (string)$contents;
        return $this;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function setBigphotoFilepath($bigphoto)
    {
        $this->bigphoto_filepath = $bigphoto;
        return $this;
    }

    public function getBigphotoFilepath()
    {
        return $this->bigphoto_filepath;
    }

    public function getBigphotoFilename()
    {
        return $this->bigphoto_filename;
    }

    public function getBigphotoTimestamp()
    {
        return $this->bigphoto_timestamp;
    }

    public function getBigphotoMimetype()
    {
        return $this->bigphoto_mimetype;
    }

    public function getBigphotoSize()
    {
        return $this->bigphoto_size;
    }

}
```

Additionaly helper entity which really holds the BLOB field must be defined i.e:

```php
namespace Entity;

use FSi\DoctrineExtensions\LoStorage\Entity\AbstractStorage;
use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\LoStorage\Mapping\Annotation as LO;

/**
 * @ORM\Entity
 */
class Photo extends AbstractStorage
{
}
```

Now it's really simple to create new article with photo:

```php
$article = new Article();
$article->setTitle('Article\'s title');
$article->setContents('Contents of the article');
$article->setBigphotoFilepath('img/src/photo1.jpg');
$em->persist($article);
$em->flush();
```

Displaying this article is also simple:

```php
echo '<h2>' . $article->getTitle() . '</h2>';
echo '<p>';
$photoUrl = str_replace('/path/to/temporary/directory/', 'http://baseurl.com/img/cache/', $article->getBigPhotoFilepath());
echo '<img src="' . $photoUrl . '" style="float: left" alt="" />';
echo $article->getContents();
echo '</p>';
```

The only assumption taken here is that the webserver is configured in such way that the URL ``http://baseurl.com/img/article/``
points to the directory `/path/to/temporary/directory`. Please bare in mind that before persisting an entity you assign
"source file" to ``bigphoto_filepath`` property and after persisting and ``$em->flush()`` this property holds filepath of some cached
file stored in directory configured in ``LoStorageListener`` and annotation ``@LO\Filepath(value="article")``. However for the whole
lifecycle of the entity this property holds valid filepath to the file with the same contents but it's not physically always the
same file. Take it as a rule of thumb that using this property (or part of it) as an URL is reasonable only when entity is not
"dirty".

## Annotations reference

### @FSi\DoctrineExtensions\LoStorage\Mapping\Annotation\Filepath (required)

**class** annotation

If you mark class with this annotation, its value defines subdirectory name where all cache files for this entity will be stored.
If value is not specified then it's taken from fully-qualified class name with namespace separators replaced with underscores.

**property** annotation

Field marked with this annotation holds filepath to the real file. You can set source file's filepath before persisting or
updating the entity and retrieve cached filepath after ``$em->flush()``. This field must not be an ORM persisted field.

The physical path where cache file will be saved is imploded from following directory levels:
- ``LoStorageListener``'s basePath
- Entity annotation ``@LO\Filepath`` value attribute (or default directory taken from class name)
- Field annotation ``@LO\Filepath`` value attribute for specific Large Object (if present)

**options:**

- ``lo`` - (``string``) _optional_ default: ``'lo'``, this is the name of this large object; each entity can contain multiple LOs
- ``value`` - (``string``) _optional_, name of subdirectory where this LO's cache files will be stored

example:

```php
    /**
     * @LO\Filepath(lo="thumbnail", value="thumb")
     */
    private $thumbnail_filepath;

    /**
     * @LO\Filepath(lo="photo", value="photo")
     */
    private $photo_filepath;
```

### @FSi\DoctrineExtensions\LoStorage\Mapping\Annotation\Filename (required)

**property** annotation

Field marked with this annotation holds filename of the Large Object. If this field is an ORM persisted field then source filename
is stored here. If it's not then annotation's value property must be set to the constant filename which always will be used for
files cached for this specific LO.

**options:**

- ``lo`` - (``string``) _optional_ default: ``'lo'``, this is the name of this large object; each entity can contain multiple LOs
- ``value`` - (``string``) _conditional_, constant filename of cached file, it's required only if filename field is not persisted,
  in the other case it's not allowed

example:

```php
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @LO\Filename(lo="thumbnail", value="thumb.jpg")
     */
    private $thumbnail_filename;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @LO\Filename(lo="photo", value="photo.jpg")
     */
    private $photo_filename;
```

### @FSi\DoctrineExtensions\LoStorage\Mapping\Annotation\Data (required)

**property** annotation

Use this annotation to mark an association of your entity with the entity holding the BLOB field (data entity). It is
recommended for this association to cascade the remove operation in order to remove data entity when owning entity is removed.
It must be @ORM\OneToOne association and the target entity must have a (BLOB) field marked with @LO\Storage. Typically the data
entity should be an empty subclass of FSi\DoctrineExtensions\LoStorage\Entity\AbstractStorage class. Any number of such
subclasses can be created to freely redistribute different Large Objects among different physical tables.

**options:**

- ``lo`` - (``string``) _optional_ default: ``'lo'``, this is the name of this large object; each entity can contain multiple LOs

example:

```php
    /**
     * @ORM\OneToOne(targetEntity="Entity\Photo", cascade={"remove"})
     * @ORM\JoinColumn(name="thumbnail_data", referencedColumnName="id", onDelete="SET NULL")
     * @LO\Data(lo="thumbnail")
     * @var Entity\Photo
     */
    private $thumbnail_data;

    /**
     * @ORM\OneToOne(targetEntity="Entity\Photo", cascade={"remove"})
     * @ORM\JoinColumn(name="photo_data", referencedColumnName="id", onDelete="SET NULL")
     * @LO\Data(lo="photo")
     * @var Entity\Photo
     */
    private $photo_data;
```

### @FSi\DoctrineExtensions\LoStorage\Mapping\Annotation\Storage (required)

**property** annotation

This annotation is used to mark a BLOB field in the data entity. It normally should not be used as long as all data entities
are subclassess of FSi\DoctrineExtensions\LoStorage\Entity\AbstractStorage class.

### @FSi\DoctrineExtensions\LoStorage\Mapping\Annotation\Timestamp (optional)

**property** annotation

Property marked with this annotation is used to hold the timestamp of the Large Object's data. This field must be persisted and
it's updated every time the corresponding data are changed. Its value is later used to compare with the timestamp of cached file.
Although this annotation is optional its usage is highly recommended for every Large Object to optimize the caching process.

**options:**

- ``lo`` - (``string``) _optional_ default: ``'lo'``, this is the name of this large object; each entity can contain multiple LOs

example:

```php
    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @LO\Timestamp(lo="thumbnail")
     */
    private $thumbnail_timestamp;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @LO\Timestamp(lo="photo")
     */
    private $photo_timestamp;
```

### @FSi\DoctrineExtensions\LoStorage\Mapping\Annotation\Mimetype (optional)

**property** annotation

Property marked with this annotation is used to hold the mimetype of the Large Object's contents. This field may be persisted or
not. If it's persited then mimetype is calculated only once during saving the data into Large Object. Otherwise it's calculated
every time the entity is loaded.

**options:**

- ``lo`` - (string) _optional_ default: ``'lo'``, this is the name of this large object; each entity can contain multiple LOs

example:

```php
    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @LO\Mimetype(lo="thumbnail")
     */
    private $thumbnail_mimetype;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @LO\Mimetype(lo="photo")
     */
    private $photo_mimetype;
```

### @FSi\DoctrineExtensions\LoStorage\Mapping\Annotation\Size (optional)

**property** annotation

Property marked with this annotation is used to hold the size of the Large Object's contents. This field may be persisted or
not. If it's persited then size is calculated only once during saving the data into Large Object. Otherwise it's calculated
every time the entity is loaded.

**options:**

- ``lo`` - (``string``) _optional_ default: ``'lo'``, this is the name of this large object; each entity can contain multiple LOs

example:

```php
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @LO\Size(lo="thumbnail")
     */
    private $thumbnail_size;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @LO\Size(lo="photo")
     */
    private $photo_size;
```
