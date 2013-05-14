# Versionable - Versionable behavioral extension for Doctrine 2

**Versionable** behaviour will automate versioning of entities. Versioning means storing different versions of the same entity,
where only one of those versions is published at the time and it's easy to change the published version back to some of the old
versions or to the new one.

Features:

- supports any total number of versions of the same entity at the same time
- supports only one published version of each versionable entity
- supports different versioning strategies in the terms of different automation of versions' status transitions
- supports different versions' statuses defined at the versioning strategy level
- supports overriding published version numbers for specific entities

The versioning strategies decides to which version the changes of versionable properties will be saved to. It is common to both of
the default strategies that the number of this version is taken from property which is marked with ``@Versionable\Versionable``
annotation and mapped to the version's field marked with ``@Versionable\Version`` annotation. After loading the versionable object
from database this field holds the number of loaded version so it is common to call it ``$loadedVersion``. If this field is set to
null before saving changes then both of two default strategies will create new version. Second responsibility of the chosen
versioning strategy is to prepare newly created version entity right after all of its fields mapped from versionable properties
are set up. Finally strategy can make some versions modifications when published version is changing. The common rule independent
from versioning strategy is that field marked with ``@Version`` annotation in versionable entity (not to be confused with the same
annotation used in version entity) holds the number of currently active (published) version. This field can be changed to the
version number of any existing version or set to null. If it is set to null then the version the changes are being saved to
(version chosen by versioning strategy) will be set as new published version. A versioning strategy can define allowed version's
statuses and automatically changes statuses of some versions as reaction to some events (i.e changing of published version).

## Creating and attaching the VersionableListener to the event manager ##

To attach the VersionableListener to your event system:

```php
$evm = new \Doctrine\Common\EventManager();
$versionableListener = new \FSi\DoctrineExtensions\Versionable\VersionableListener();
$evm->addEventSubscriber($versionableListener);
// now this event manager should be passed to entity manager constructor
```

``VersionableListener`` has some usefull methods to manipulate versions:
- ``loadVersion($objectManager, $object, $version = null)`` - load version with specific number into specified object which is managed by specified ``EntityManager``
- ``setVersionForEntity($objectManager, $object, $version = null)`` - override published version number for specified object
- ``getVersionForEntity($objectManager, $object)`` - return overrided version number for specific entity if any
- ``setVersionForId($objectManager, $class, $id, $version = null)`` - override published version number for object of specified class and specified identity
- ``getVersionForId($objectManager, $class, $id)`` - return overrided version number for object of specified class and specified identity
- ``getVersionsForClass($objectManager, $class)`` - return an array of overrided version numbers for objects of specified class

## Simple entity annotations and usage example ##

Here is an example of an entity with versionable properties using SimpleStrategy:

```php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Versionable\Mapping\Annotation as Versionable;

/**
 * @ORM\Entity
 * @Versionable\Versionable(mappedBy="versions")
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Versionable\Version
     * @ORM\Column(type="integer", nullable=true)
     * @var integer
     */
    private $publishedVersion;

    /**
     * @Versionable\Versionable
     * @var integer
     */
    private $version;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $date;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $title;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $contents;

    /**
     * @ORM\OneToMany(targetEntity="ArticleVersion", mappedBy="article", indexBy="version")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $versions;

    public function __construct()
    {
        $this->versions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
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

    public function setPublishedVersion($version = null)
    {
        $this->publishedVersion = $version;
        return $this;
    }

    public function getPublishedVersion()
    {
        return $this->publishedVersion;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version = null)
    {
        $this->version = (int)$version;
        return $this;
    }

    public function getVersions()
    {
        return $this->versions;
    }
}
```

The associated version entity could be defined as follows:

```php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Versionable\Mapping\Annotation as Versionable;

/**
 * @ORM\Entity
 */
class ArticleVersion
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Versionable\Version
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $version;

    /**
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $date;

    /**
     * @ORM\Column
     * @var string
     */
    private $title;

    /**
     * @ORM\Column
     * @var string
     */
    private $contents;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="versions")
     * @ORM\JoinColumn(name="article", referencedColumnName="id")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $article;

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

    public function getVersion()
    {
        return $this->version;
    }

}
```

Now it's simple to create new article with first version as it was a standard entity:

```php
$article = new Article();
$article->setTitle('Article\'s title');
$article->setContents('Contents of the article');
$em->persist($article);
$em->flush();
```

Modifying article (its currently published version) looks also as usual:

```php
$article->setTitle('New article\'s title'');
$article->setContents('New contents of the article');
$em->flush();
```

In order to save changes in a new version we have to:

```
$article->setTitle('Newest article\'s title'');
$article->setContents('Newest contents of the article');
$article->setVersion();
$em->flush();
```

Please note that the new version is not automatically published. In order to do that you have to additionally call
``setPublishedVersion()`` before ``flush()`` :

```
$article->setTitle('Newest article\'s title'');
$article->setContents('Newest contents of the article');
$article->setVersion();
$article->setPublishedVersion();
$em->flush();
```

It is possible to load any previous version of article:

```php
$versionableListener->loadVersion($article, 1);
```

Or get some version without loading it into versionable article:

```php
$version1 = $article->getVersions()->get(1);
```

The default versioning strategy used in this example (``FSi\DoctrineExtensions\Versionable\Strategy\SimpleStrategy``) does not use
version statuses so the version entity should not have any field marked with ``@Versionable\Status`` annotation.

## Complex entity annotations and usage example ##

Here is an example of an entity with versionable properties using ArchiveStrategy:

```php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Versionable\Mapping\Annotation as Versionable;

/**
 * @ORM\Entity
 * @Versionable\Versionable(mappedBy="versions", strategy="FSi\DoctrineExtensions\Versionable\Strategy\ArchiveStrategy")
 */
class ArchivableArticle
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Versionable\Version
     * @ORM\Column(type="integer", nullable=true)
     * @var integer
     */
    private $publishedVersion;

    /**
     * @Versionable\Versionable(targetField="version")
     * @var integer
     */
    private $loadedVersion;

    /**
     * @Versionable\Versionable(targetField="status")
     * @var integer
     */
    private $loadedVersionStatus;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $date;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $title;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $contents;

    /**
     * @ORM\OneToMany(targetEntity="ArchivableArticleVersion", mappedBy="article", indexBy="version")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $versions;

    public function __construct()
    {
        $this->versions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function getDate()
    {
        return $this->date;
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

    public function setPublishedVersion($version = null)
    {
        $this->publishedVersion = $version;
        return $this;
    }

    public function getPublishedVersion()
    {
        return $this->publishedVersion;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version = null)
    {
        $this->version = (int)$version;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status = null)
    {
        $this->status = (int)$status;
    }

    public function getVersions()
    {
        return $this->versions;
    }
}
```

The associated version entity could be defined as follows:

```php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Versionable\Mapping\Annotation as Versionable;

/**
 * @ORM\Entity
 */
class ArchivableArticleVersion
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Versionable\Version
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $version;

    /**
     * @Versionable\Status
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $status;

    /**
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $date;

    /**
     * @ORM\Column
     * @var string
     */
    private $title;

    /**
     * @ORM\Column
     * @var string
     */
    private $contents;

    /**
     * @ORM\ManyToOne(targetEntity="ArchivableArticle", inversedBy="versions")
     * @ORM\JoinColumn(name="article", referencedColumnName="id")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $article;

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

    public function getVersion()
    {
        return $this->version;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = (int)$status;
    }
}
```

Now it's simple to create new article with first version as it was a standard entity:

```php
$article = new Article();
$article->setTitle('Article\'s title');
$article->setContents('Contents of the article');
$em->persist($article);
$em->flush();
```

Modifying article (its currently published version) looks also as usual:

```php
$article->setTitle('New article\'s title'');
$article->setContents('New contents of the article');
$em->flush();
```

In order to save changes in a new version we have to:

```php
$article->setTitle('Newest article\'s title'');
$article->setContents('Newest contents of the article');
$article->setVersion();
$em->flush();
```

Please note that the new version is not automatically published. In order to do that you have to additionally call
``setPublishedVersion()`` before ``flush()`` :

```php
$article->setTitle('Newest article\'s title'');
$article->setContents('Newest contents of the article');
$article->setVersion();
$article->setPublishedVersion();
$em->flush();
```

It is possible to load any previous version of article:

```php
$versionableListener->loadVersion($article, 1);
```

Or get some version without loading it into versionable article:

```php
$version1 = $article->getVersions()->get(1);
```

The versioning strategy used in this example (``FSi\DoctrineExtensions\Versionable\Strategy\ArchiveStrategy``) requires the version
entity to have field marked with ``@Versionable\Status`` annotation (preferably of type integer). This strategy defines three different
status values that a version can have: ``STATUS_PUBLISHED``, ``STATUS_DRAFT`` and ``STATUS_ARCHIVE``. When a new version is created
the strategy sets its status to STATUS_DRAFT (if it's not set before to something else). When a version is published then its
status is set to ``STATUS_CURRENT`` and the status of version that was published before is set to ``STATUS_ARCHIVE``.

## Using VersionableTreeWalker ##

To simplify querying versionable entities there's a ``VersionableTreeWalker`` which can be added as a hint to the query (
``Query::HINT_CUSTOM_TREE_WALKERS``). It modifies query in order to automagically load proper versions along with queried
entities using one ``SELECT`` query.

```php
$query = $em->createQuery("SELECT a FROM Article AS a");
$query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, array('FSi\DoctrineExtensions\Versionable\Query\VersionableTreeWalker'));
$articles = $query->execute();
```

Now objects returned in ``$articles`` collection have already loaded all versionable fields from their published versions or
overrided ones.

## Annotations reference ##

### @FSi\DoctrineExtensions\Versionable\Mapping\Annotation\Versionable ###

**class** annotation

Class marked with this annotation is versionable which means that its non-persistent properties marked with @Versionable are
physically stored in a version entities rather than in versionable entity.

**options:**

- ``mappedBy`` - (``string``) _required_ , this is the name of the association to version entity for this class
- ``strategy`` - (``string``) _optional_ , default: ``'FSi\DoctrineExtensions\Versionable\Strategy\SimpleStrategy'`` , class of
  versioning strategy which will be used for this class' objects. This class must implement
  ``FSi\DoctrineExtensions\Versionable\Strategy\StrategyInterface``.

example:

```php
/**
 * @ORM\Entity
 * @Versionable\Versionable(mappedBy="versions", strategy="FSi\DoctrineExtensions\Versionable\Strategy\ArchiveStrategy")
 */
class ArchivableArticle
{

    ...

    /**
     * @ORM\OneToMany(targetEntity="ArchivableArticleVersion", mappedBy="article", indexBy="version")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $versions;

    public function __construct()
    {
        $this->versions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    ...

}
```

**property** annotation

Property marked with this annotation is automatically copied from/into associated field of version entity. Such a property can
not be persistent, while associated field in version entity have to be persistent.

**options:**

- ``targetField`` - (string) _optional_, name of persistent field in version entity used to hold the real value in database, it's
  default is the marked property's name

example:

```php
    /**
     * @Versionable\Versionable(targetField="title")
     * @var string
     */
    private $title;

    /**
     * @Versionable\Versionable
     * @var string
     */
    private $contents;
```

Please note that the properties mapped to fields marked with ``@Versionable\Version`` and ``@Versionable\Status`` annotation in
version entity have some special meaning. The first is required to exists in each versionable entity and the second is required
if ``ArchiveStrategy`` is used.

### @FSi\DoctrineExtensions\Translatable\Mapping\Annotation\Version ###

**property** annotation

This annotation have two meanings. In version entity it has to be used to mark field that will hold the version number. This
field should be of type integer and is automatically set for every new version created and cannot be changed during the whole
version lifecycle. In versionable entity it has to be used to mark field that will hold the number of currently published version.

example in version entity:

```php
    /**
     * @Versionable\Version
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $version;
```

example in versionable entity:

```php
    /**
     * @Versionable\Version
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $publishedVersion;
```

### @FSi\DoctrineExtensions\Translatable\Mapping\Annotation\Status ###

**property** annotation

This annotation is used to mark field in version entity that will hold the version status. This field is only required for
specific versioning strategies (like ``ArchiveStrategy``) and each strategy can define its own behavior of this field. The default
ArchiveStrategy requires it to be of type integer and allow three statuses like ``STATUS_CURRENT``, ``STATUS_ARCHIVE``
and ``STATUS_DRAFT``.

example:

```php
    /**
     * @Versionable\Status
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $status;
```

