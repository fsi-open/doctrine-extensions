<?php


namespace FSi\DoctrineExtensions\Tests\Uploadable;

use FSi\DoctrineExtensions\Tests\Tool\BaseORMTest;

class AnnotationTest extends BaseORMTest
{
    const BASE = 'FSi\\DoctrineExtensions\\Tests\\Uploadable\\Fixture\\Annotation\\';

    protected function setUp()
    {
        parent::setUp();
        $this->_em = $this->getEntityManager();
    }

    public function testMissingTargetField()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User1');
    }

    public function testEmptyTargetField()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User2');
    }

    public function testWrongTargetField()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User3');
    }

    public function testTargetFieldIsAlsoMapped()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User4');
    }

    public function testUploadableIsNotMapped()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User5');
    }

    public function testKeyLengthIsZero()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User6');
    }

    public function testKeyLengthIsNegative()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User7');
    }

    public function testKeyLengthIsNotNumeric()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User8');
    }

    public function testKeymakerIsNotInstanceOfKeymakerInterface()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User9');
    }

    public function testKeymakerIsWrong()
    {
        $this->setExpectedException('FSi\\DoctrineExtensions\\Uploadable\\Exception\\AnnotationException');
        $this->_uploadableListener->getExtendedMetadata($this->_em, self::BASE . 'User10');
    }

    public function getUsedEntityFixtures()
    {
        return array();
    }
}
