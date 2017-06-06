# Mapping extension for Doctrine 2

**Mapping** extension makes it easy to map additional metadata for ORM/ODM event listeners.
It supports **Yaml**, **Xml** and **Annotation** drivers which will be chosen depending on
currently used mapping driver for ObjectManager which the event listener is attached to.
**Mapping** extension also provides abstraction layer of **EventArgs** to make it possible
to use single listeners for different object managers like **ODM** and **ORM**. The main purpose
of this component is to make it easy to implement specific listeners and their additional mapping
drivers. It's achieved mostly by using and extending FSi Metadata component.

## Creating a mapped extension ##

First, lets asume we will use ``Extension`` namespace for our additional
extension library. You should create an ``Extension`` directory in your library
or ``vendor`` directory. After some changes your project might look like:

    project
        ...
        bootstrap.php
        vendor
            Extension
            ...
    ...

Now you can use any namespace autoloader class and register this namespace. We
will use ``Doctrine\Common\ClassLoader`` for instance:

    // path is related to boostrap.php location for example
    $classLoader = new \Doctrine\Common\ClassLoader('Extension', "vendor");
    $classLoader->register();

Now lets create some files which are necessary for our extension:

    project
        ...
        bootstrap.php
        vendor
            Extension
                Encoder
                    Mapping
                        Driver
                            Annotation.php
                        Annotations.php
                    EncoderListener.php
    ...

**Notice:** that extension will look for mapping in ``ExtensionNamespace/Mapping``
directory. And **Driver** directory should be named as ``Driver``. These are the conventions
of **Mapping** extension.

That is all we will need for now. As you may have noticed, we will create an encoding
listener which will encode your fields by specified annotations. In real life it
may not be useful since object will not know how to match the value.

## Define available annotations and setup drivers ##

Edit **Annotations.php** file:

```php
// file: vendor/Extension/Encoder/Mapping/Annotations.php

namespace Extension\Encoder\Mapping;

use Doctrine\Common\Annotations\Annotation;

final class Encode extends Annotation
{
    public $type = 'md5';
    public $secret;
}
```

Edit **Annotation.php** driver file:

```php
// file: vendor/Extension/Encoder/Mapping/Driver/Annotation.php

namespace Extension\Encoder\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use FSi\Component\Metadata\ClassMetadataInterface;
use FSi\DoctrineExtensions\Mapping\Driver\AbstractAnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class Annotation extends AbstractAnnotationDriver
{

    /**
    * {@inheritdoc}
    */
    protected function loadExtendedClassMetadata(ClassMetadata $baseClassMetadata, ClassMetadataInterface $extendedClassMetadata)
    {
        $class = $extendedClassMetadata->getReflectionClass();
        $reader = $this->getAnnotationReader();
        // check only property annotations
        foreach ($class->getProperties() as $property) {
            // skip inherited properties
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // now let's check if property has our annotation
            if ($encode = $reader->getPropertyAnnotation($property, 'Extension\Encoder\Mapping\Encode')) {
                $field = $property->getName();
                // check if field is mapped
                if (!$meta->hasField($field)) {
                    throw new \Exception("Field is not mapped as a persistent field");
                }
                // validate encoding type
                if (!in_array($encode->type, array('sha1', 'md5'))) {
                    throw new \Exception("Invalid encoding type supplied");
                }
                // allow encoding only strings
                $mapping = $meta->getFieldMapping($field);
                if ($mapping['type'] != 'string') {
                    throw new \Exception("Only strings can be encoded");
                }
                // store the metadata
                $extendedClassMetadata->addPropertyMetadata($field, 'type', $encode->type);
                $extendedClassMetadata->addPropertyMetadata($field, 'secret', $encode->secret)
            }
        }
    }
}
```

A little explanation about ``$extendedClassMetadata`` argument is needed here. Interface for ``loadExtendedClassMetadata()``
method requires a parameter implementing ``FSi\Component\Metadata\ClassMetadataInterface``, but specfic extension can
define any custom class implementing this interface and place it as ``Mapping\ClassMetadata`` in the
extension's namespace. In our example it would be ``Extension\Encoder\Mapping\ClassMetadata``.
When such a class is not defined then the standard ``FSi\Component\Metadata\ClassMetadata``
will be used. It should be powerful enough for most cases.

## Finally, let's create the listener ##

**Notice:** This version of listener will support only ORM Entities

```php
// file: vendor/Extension/Encoder/EncoderListener.php

namespace Extension\Encoder;

use Doctrine\Common\EventArgs;
use FSi\Component\Metadata\ClassMetadata;
use FSi\DoctrineExtensions\Mapping\MappedEventSubscriber;

class EncoderListener extends MappedEventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'onFlush'
        );
    }

    public function onFlush(EventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // check all pending updates
        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $meta = $em->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            $extendedMeta = $this->getExtendedMetadata($em, $meta->name);
            $this->encode($em, $object, $extendedMeta);
        }
        // check all pending insertions
        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $meta = $em->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            $extendedMeta = $this->getExtendedMetadata($em, $meta->name);
            $this->encode($em, $object, $extendedMeta);
        }
    }

    protected function getNamespace()
    {
        // mapper must know the namespace of extension
        return __NAMESPACE__;
    }

    private function encode($em, $object, $extendedMeta)
    {
        $meta = $em->getClassMetadata(get_class($object));
        foreach ($extendedMeta->getAllPropertyMetadata() as $field => $options) {
            $value = $meta->getReflectionProperty($field)->getValue($object);
            $method = $options['type'];
            $encoded = $method($options['secret'].$value);
            $meta->getReflectionProperty($field)->setValue($object, $encoded);
        }
        // recalculate changeset
        $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $object);
    }
}
```

Our ``Encoder`` extension is ready, now if we want to test it, we need to attach our ``EncoderListener``
to the ``EventManager`` and create an entity with some fields to encode.

### Attaching the EncoderListener ###

```php
$evm = new \Doctrine\Common\EventManager();
$encoderListener = new \Extension\Encoder\EncoderListener;
$evm->addEventSubscriber($encoderListener);
// now this event manager should be passed to entity manager constructor
```

### Create an entity with some fields to encode ###

```php
namespace YourNamespace\Entity;

use Doctrine\ORM\Mapping as ORM;
use Extension\Encoder\Mapping as EXT;

/**
 * @ORM\Table(name="test_users")
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @EXT\Encode(type="sha1", secret="xxx")
     * @ORM\Column(length=64)
     */
    private $name;

    /**
     * @EXT\Encode(type="md5")
     * @ORM\Column(length=32)
     */
    private $password;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
```

Now if you create a new ``User``, you will get encoded fields in database.

**Notice:** event adapter uses ``EventArgs`` to recognize with which manager
we are dealing with. It also uses event arguments to retrieve manager and transforms
the method call in its way. You can extend the event adapter in order to add some
specific methods for each manager.

## Customizing event adapter for specific functions ##

In most cases event listener will need specific functionality which will differ
for every object manager. For instance, a query to load users will differ. The
example bellow will illustrate how to handle such situations. You will need to
extend default ORM and ODM event adapters to implement specific functions which
will be available through the event adapter. First we will need to follow the
mapping convention to use those extension points.

### Extending default event adapters ###

Update your directory structure:

    project
        ...
        bootstrap.php
        vendor
            Extension
                Encoder
                    Mapping
                        Driver
                            Annotation.php
                        Event
                            Adapter
                                ORM.php
                                ODM.php
                        Annotations.php
                    EncoderListener.php
    ...

Now **Mapping** extension will automatically create event adapter instances
from the extended ones.

Create extended ORM event adapter:

```php
// file: vendor/Extension/Encoder/Mapping/Event/Adapter/ORM.php

namespace Extension\Encoder\Mapping\Event\Adapter;

use FSi\DoctrineExtensions\Mapping\Event\Adapter\ORM as BaseAdapterORM;

class ORM extends BaseAdapterORM
{
    public function someSpecificMethod()
    {
    
    }
}
```

Create extended ODM event adapter:

```
// file: vendor/Extension/Encoder/Mapping/Event/Adapter/ODM.php

namespace Extension\Encoder\Mapping\Event\Adapter;

use FSi\DoctrineExtensions\Mapping\Event\Adapter\ODM as BaseAdapterODM;

class ODM extends BaseAdapterODM
{
    public function someSpecificMethod()
    {
    
    }
}
```
