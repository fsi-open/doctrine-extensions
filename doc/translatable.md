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
- supports manipulating multiple translations in one ORM transaction

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
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="\FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository")
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
     * @Translatable\Translatable(mappedBy="translations")
     * @var Doctrine\Common\Collections\ArrayCollection|Comment[]
     */
    private $comments;

    /**
     * @Translatable\Locale
     * @var string
     */
    private $locale;

    /**
     * @ORM\OneToMany(targetEntity="ArticleTranslation", mappedBy="article", indexBy="locale")
     * @var Doctrine\Common\Collections\ArrayCollection|ArticleTranslation[]
     */
    private $translations;

    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @ORM\PostLoad()
     */
    public function postLoad()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
    }

    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
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

Please note the ``postLoad()`` method which is necessary to initialize translatable collection ``$comments`` before the
real translated collection would be loaded from translation entity.

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
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="articleTranslation")
     * @var Doctrine\Common\Collections\ArrayCollection|Comment[]
     */
    private $comments;

    /**
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="translations")
     * @ORM\JoinColumn(name="article", referencedColumnName="id")
     * @var Doctrine\Common\Collections\ArrayCollection
     */
    private $article;

    /**
     * @Translatable\Locale
     * @ORM\Column(type="string", length=2)
     * @var string
     */
    private $locale;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
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

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
    }

    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
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

Additionally ``Comment`` is an ordinary entity associated with ``ArticleTranslation`` and its contents is irrelevant for
this example.

To operate with translations let's assume that our default locale is english:

```php
$translatableListener->setLocale('en');
```

Now it's really simple to create new article with some translation:

```php
$article = new Article();
$article->setTitle('Article\'s title');
$article->setContents('Contents of the article');
$article->addComment(new Comment('Comments contents'));
$em->persist($article);
$em->flush();
```

Adding another translation may look like this:

```php
$article->setLocale('pl');
$article->setTitle('Tytuł artykułu');
$article->setContents('Treść artykułu');
$article->addComment(new Comment('Treść pierwszego komentarza'));
$article->addComment(new Comment('Treść drugiego komentarza'));
$em->flush();
```

Retrieving article from database with currently set default locale is as simple as:

```php
$article = $em->find($articleId);
echo $article->getTitle();
echo $article->getContents();
echo count($article->getComments());
$translatableListener->setLocale('pl');
$em->refresh($article);
echo $article->getTitle();
echo $article->getContents();
echo count($article->getComments());
```

Thanks to the ``indexBy`` attribute set on translations association we can access
translations in all locales regardless from current default locale:

```php
$article = $em->find($articleId);
echo $article->getTranslation('en')->getTitle();
echo $article->getTranslation('en')->getContent();
echo count($article->getTranslation('en')->getComments());
echo $article->getTranslation('pl')->getTitle();
echo $article->getTranslation('pl')->getContent();
echo count($article->getTranslation('pl')->getComments());
```

## Using ``TranslatableRepository``

This extension also provides ``TranslatableRepository`` class with some helper methods
which make manipulating multiple translations at once easier. In order to use it
you must set ``entityRepository`` on you translatable entity like in this example:

```php
/**
 * @ORM\Entity(repositoryClass="\FSi\DoctrineExtensions\Translatable\Entity\Repository\TranslatableRepository")
 */
class Article
{
    ...
}
```

### Manipulating translations in different locales in one transaction

Then you can use helper methods of ``TranslatableRepository`` like this:

```php
$article = $em->find($articleId);
$repository = $em->getRepository('Article');
$repository->getTranslation($article, 'en')->setTitle('New article title');
$em->flush();
```

**Heads up!!** if you modified some fields in a translation entity and also modify
corresponding fields in base (translatable) entity then values in translation
entity would be overwritten by values from base entity. Take a look at the example:

```php
$translatableListener->setLocale('en');
$article = $em->find($articleId);
$repository = $em->getRepository('Article');
// as long as current locale is 'en' this will modify english title
$article->setTitle('New article title 1');
// this will also modify english title
$repository->getTranslation($article, 'en')->setTitle('New article title 2');
$em->flush();
// after flush and refresh of the entity both access methods will return
// 'New article title 1'
echo $repository->getTranslation($article, 'en')->getTitle();
echo $article->getTitle();
```

The ``getTranslation()`` method creates new translation for specified locale if
the one does not exist already. Sometimes it's useful to check if translation
exists before accessing it. This is especially important when translations have
some fields which cannot be saved as ``null``:

```php
$article = $em->find($articleId);
$repository = $em->getRepository('Article');
$title = null;
if ($repository->hasTranslation($article, 'en')) {
    $title = $repository->getTranslation($article, 'en')
        ->getTitle('New article title');
}
```

### Selecting entities with current translations in one query

It is common task where using translations to select entities along with their
translations in the currently set locale. It's easy using another helper method
on ``TranslatableRepository``.

```php
$translatableListener->setLocale('de');
$translatableListener->setDefaultLocale('en');
$repository = $em->getRepository('Article');
$qb = $repository->createTranslatableQueryBuilder('a', 't', 'dt');
echo $qb->getQuery()->getDql();
```

Displayed DQL will be something like:

```
SELECT a, t, dt FROM Article a LEFT JOIN a.translations t WITH t.locale = :locale LEFT JOIN a.translations dt WITH dt.locale = :deflocale
```

You can freely extend returned QueryBuilder i.e to query by values of translated fields:

```php
$translatableListener->setLocale('en');
$repository = $em->getRepository('Article');
$qb = $repository->createTranslatableQueryBuilder('a', 't', 'dt');
$qb->where('t.Title LIKE ?', '%article%');
```

** Heads Up!!** ``QueryBuilder`` object returned by ``createTranslatableQueryBuilder()``
extends ``\Doctrine\ORM\QueryBuilder`` and it's ``getQuery()`` method returns
query which has already set custom hydration mode. This custom hydration mode is
necessary in order to hydrate objects along with their translations and
substitute translated values into the main objects during single query execution.
However this hydration mode will not be used during ``$query->getResult()`` (with
no arguments). In order to gain this speedup you must get results through
``$query->execute()`` which respects the custom hydration mode set on ``$query``
object.

### Finding entities by translatable fields

``TranslatableRepository`` has two additional methods which can be used to find translatable
entities by values of their regular ORM fields or translatable fields. Using these methods
you don't have to know whether field of entity is regular ORM mapped field or translatable
field. Both methods takes currently set locale and default locale into consideration.

```php
$repository = $em->getRepository('Article');
$articles = $repository->findTranslatableBy(array(
    'title' => 'some title'
));
$article = $repository->findTranslatableOneBy(array(
    'date' => '2014-01-01 00:00:00',
));
```

## Translatable QueryBuilder

When all of the above methods of retrieving translatable entities are not enough there is
``FSi\DoctrineExtensions\Translatable\Query\QueryBuilder`` class which gives full control
over the logic of retrieving entities and their translations. Below are some examples of
using it. For more take a look at [QueryBuilder](../lib/FSi/DoctrineExtensions/Translatable/Query/QueryBuilder.php)

### Build query to select only entities with translations in current locale.

```php
use FSi\DoctrineExtensions\Translatable\Query\QueryBuilder as TranslatableQueryBuilder

$qb = new TranslatableQueryBuilder($em);
$qb
    ->from('Article', 'a')
    ->select('a')
    ->joinAndSelectCurrentTranslations('a.translations', Expr\Join::INNER_JOIN);
```

### Build query to select translatable entities by value of some translatable field.

```php
use FSi\DoctrineExtensions\Translatable\Query\QueryBuilder as TranslatableQueryBuilder

$qb = new TranslatableQueryBuilder($em);
$qb
    ->from('Article', 'a')
    ->select('a')
    ->addTranslatableWhere('a', 'title', 'some title');
```

### Build query to select translatable entities with translatable field matching some condition.

```php
use FSi\DoctrineExtensions\Translatable\Query\QueryBuilder as TranslatableQueryBuilder

$qb = new TranslatableQueryBuilder($em);
$qb
    ->from('Article', 'a')
    ->select('a')
    ->addWhere(sprintf(
        '%s LIKE "%%Obama%%"',
        $qb->getTranslatableFieldExpr('a', 'title')
    );
```

## Annotations reference ##

### @FSi\DoctrineExtensions\Translatable\Mapping\Annotation\Translatable ###

**property** annotation

Property marked with this annotation is automatically copied from/into associated field translation entity. Such a property can
not be persistent, while associated field in translation can be persistent or not i.e. when it's also an uploadable field
(for more information see [uploadable.md](uploadable.md)).

**options:**

- ``mappedBy`` - (``string``) _required_ , this is the name of the association to translation entity for this property
- ``targetField`` - (``string``) _optional_, name of persistent field in translation entity used to hold the real translation, it's
  default is the marked property name 

example:

```php
# Some entity
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

    /**
     * @Translatable\Translatable(mappedBy="translations")
     * @var \SplFileInfo|\FSi\DoctrineExtensions\Uploadable\File
     */
    private $image;
```


```php
# And it's translation
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $contents;

    /**
     * @var \SplFileInfo|\FSi\DoctrineExtensions\Uploadable\File
     */
    protected $image;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Uploadable\Uploadable(targetField="image")
     */
    protected $imageKey;
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
