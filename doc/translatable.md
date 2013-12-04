# Translatable - Translatable behavioral extension for Doctrine 2 #

**Translatable** behaviour will automate storing and retrieving translations in entities. Translated values are persisted in
specialized translation entities associated with base entity. Each base entity with translatable properties has to be associated
with at least one translation entity. Retrieving en entity with translatable properties copies their values from the translation
for current locale. Changing values of these properties and flushing changes copies values to the appropriate translation
entity.

Features:

- supports multiple translatable properties in entity
- supports grouping translations of translatable properties of the same entity in different translation entities
- supports removing translation entity for specific locale by setting all translatable properties to null
- supports string or integer type for locale field; defining locale as an association to another entity is not supported
- supports indexing translations collection by locale (or some other field) which simplifies accessing different translations
  at the same time from one instance of base entity

## Creating and attaching the TranslatableListener to the event manager ##

To attach the ``TranslatableListener`` to your event system:

```php
$evm = new \Doctrine\Common\EventManager();
$translatableListener = new \FSi\DoctrineExtensions\Translatable\TranslatableListener();
$evm->addEventSubscriber($translatableListener);
// now this event manager should be passed to entity manager constructor
```

``TranslatableListener`` has two options:

- ``locale`` (``mixed``) - the current locale, default: ``null``
- ``defaultLocale`` (``mixed``) - the default locale to be used as a fallback, default: ``null``

The current locale has to be set in order to automatically populate translatable fields with values from translation entity.
If there is no translation in current locale and the default locale is set then translation in default locale will be loaded.

## Simple entity annotations and usage example ##

Here is an example of an entity with translatable properties:

```php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="datetime")
     * @var string
     */
    private $date;

    /**
     * @Translatable\Locale
     * @var string
     */
    private $locale;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $title;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $contents;

    /**
     * @ORM\OneToMany(targetEntity="ArticleTranslation", mappedBy="article", indexBy="locale")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function setLocale($locale)
    {
        $this->locale = (string)$locale;
        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function hasTranslation($locale)
    {
        return isset($this->translations[$locale]);
    }

    public function getTranslation($locale)
    {
        if ($this->hasTranslation($locale)) {
            return $this->translations[$locale];
        } else {
            return null;
        }
    }
}
```

The associated translation entity could be defined as follows:

```php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use FSi\DoctrineExtensions\Translatable\Mapping\Annotation as Translatable;

/**
 * @ORM\Entity
 */
class ArticleTranslation
{
    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer $id
     */
    private $id;

    /**
     * @Translatable\Locale
     * @ORM\Column(type="string", length=2)
     * @var string
     */
    private $locale;

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
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="translations")
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

    public function setLocale($locale)
    {
        $this->locale = (string)$locale;
        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

}
```

To operate with translations let's assume that our default locale is english:

```php
$translatableListener->setLocale('en');
```

Now it's really simple to create new article with some translation:

```php
$article = new Article();
$article->setTitle('Article\'s title');
$article->setContents('Contents of the article');
$em->persist($article);
$em->flush();
```

Adding another translation may look like this:

```php
$article->setLocale('pl');
$article->setTitle('Tytuł artykułu');
$article->setContents('Treść artykułu');
$em->flush();
```

Retrieving article from database with currently set default locale is as simple as:

```php
$article = $em->find($articleId);
echo $article->getTitle();
echo $article->getContents();
$translatableListener->setLocale('pl');
$em->refresh($article);
echo $article->getTitle();
echo $article->getContents();
```

Thanks to the ``indexBy`` attribute set on translations association we can access
translations in all locales regardless from current default locale:

```php
$article = $em->find($articleId);
echo $article->getTranslation('en')->getTitle();
echo $article->getTranslation('en')->getContent();
echo $article->getTranslation('pl')->getTitle();
echo $article->getTranslation('pl')->getContent();
```

As long as translations are regular entities you can manipulate them in as usual:

```php
$article = $em->find($articleId);
$article->getTranslation('en')->setTitle('New article title');
$em->flush();
```

**Heads up!!** if you modified some fields in a translation entity and also modify
corresponding fields in base (translatable) entity then values in translation
entity would be overwritten by value from base entity. Take a look at the example:

```php
$translatableListener->setLocale('en');
$article = $em->find($articleId);
// as long as current locale is 'en' this will modify english title
$article->setTitle('New article title 1');
// this will also modify english title
$article->getTranslation('en')->setTitle('New article title 2');
$em->flush();
// after flush and refresh of the entity both access methods will return
// 'New article title 1'
echo $article->getTranslation('en')->getTitle();
echo $article->getTitle();
```

## Annotations reference ##

### @FSi\DoctrineExtensions\Translatable\Mapping\Annotation\Translatable ###

**property** annotation

Property marked with this annotation is automatically copied from/into associated field translation entity. Such a property can
not be persistent, while associated field in translation entity have to be persistent.

**options:**

- ``mappedBy`` - (``string``) _required_ , this is the name of the association to translation entity for this property
- ``targetField`` - (``string``) _optional_, name of persistent field in translation entity used to hold the real translation, it's
  default is the marked property name 

example:

```php
    /**
     * @Translatable\Translatable(mappedBy="translations", targetField="title")
     * @var string
     */
    private $title;

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var string
     */
    private $contents;
```

### @FSi\DoctrineExtensions\Translatable\Mapping\Annotation\Locale ###

**property** annotation

This annotation have to be used to mark property that will hold the current locale of a translatable entity. It also has to
mark the persistent field in translation entity that will persist the locale value in database. It's up to developer to decide
how should the locale value look like. It could be a string (like locale name), an integer (identity of some locale entity)
but it definitely can not be an association.

example in translatable entity:

```php
    /**
     * @Translatable\Locale
     * @var string
     */
    private $locale;
```

example in translation entity:

```php
    /**
     * @Translatable\Locale
     * @ORM\Column(type="string", length=2)
     * @var string
     */
    private $locale;
```
