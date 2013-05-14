<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Mapping\Event\Adapter;

use FSi\Component\Reflection\ReflectionProperty;
use FSi\DoctrineExtensions\Mapping\Event\AdapterInterface;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

/**
 * Doctrine event adapter for ODM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ODM implements AdapterInterface
{
    /**
     * @var EventArgs
     */
    private $args;

    /**
     * {@inheritdoc}
     */
    public function setEventArgs(EventArgs $args)
    {
        $this->args = $args;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainObjectName()
    {
        return 'Document';
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerName()
    {
        return 'ODM';
    }

    /**
     * Extracts identifiers from object or proxy
     *
     * @param DocumentManager $dm
     * @param object $object
     * @param bool $single
     * @return mixed - array or single identifier
     */
    public function extractIdentifier(DocumentManager $dm, $object, $single = true)
    {
        $meta = $dm->getClassMetadata(get_class($object));
        if ($object instanceof Proxy) {
            $id = $dm->getUnitOfWork()->getDocumentIdentifier($object);
        } else {
            $id = ReflectionProperty::factory($meta->name, $meta->identifier)->getValue($object);
        }

        if ($single || !$id) {
            return $id;
        } else {
            return array($meta->identifier => $id);
        }
    }

    /**
     * Call event specific method
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $method = str_replace('Object', $this->getDomainObjectName(), $method);
        return call_user_func_array(array($this->args, $method), $args);
    }

    /**
     * Get the object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param Object $object
     * @return array
     */
    public function getObjectChangeSet(UnitOfWork $uow, $object)
    {
        return $uow->getDocumentChangeSet($object);
    }

    /**
     * Get the single identifier field name
     *
     * @param ClassMetadataInfo $meta
     * @return string
     */
    public function getSingleIdentifierFieldName(ClassMetadataInfo $meta)
    {
        return $meta->identifier;
    }

    /**
     * Recompute the single object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param ClassMetadataInfo $meta
     * @param Object $object
     * @return void
     */
    public function recomputeSingleObjectChangeSet(UnitOfWork $uow, ClassMetadataInfo $meta, $object)
    {
        $uow->recomputeSingleDocumentChangeSet($meta, $object);
    }

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectUpdates(UnitOfWork $uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }

    /**
     * Get the scheduled object insertions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectInsertions(UnitOfWork $uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }

    /**
     * Get the scheduled object deletions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectDeletions(UnitOfWork $uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }

    /**
     * Sets a property value of the original data array of an object
     *
     * @param UnitOfWork $uow
     * @param string $oid
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function setOriginalObjectProperty(UnitOfWork $uow, $oid, $property, $value)
    {
        $uow->setOriginalDocumentProperty($oid, $property, $value);
    }

    /**
     * Clears the property changeset of the object with the given OID.
     *
     * @param UnitOfWork $uow
     * @param string $oid The object's OID.
     */
    public function clearObjectChangeSet(UnitOfWork $uow, $oid)
    {
        $uow->clearDocumentChangeSet($oid);
    }
}