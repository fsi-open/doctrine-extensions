<?php

error_reporting(E_ALL | E_STRICT);

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

if (!class_exists('PHPUnit_Framework_TestCase') ||
    version_compare(PHPUnit_Runner_Version::id(), '3.5') < 0
) {
    die('PHPUnit framework is required, at least 3.5 version');
}

if (!class_exists('PHPUnit_Framework_MockObject_MockBuilder')) {
    die('PHPUnit MockObject plugin is required, at least 1.0.8 version');
}
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    die('Install vendors using command: composer.phar update');
}

$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->add('FSi\\DoctrineExtension\\Tests', __DIR__);

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    VENDOR_PATH.'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'FSi\\DoctrineExtension\\Translatable\\Mapping\\Annotation',
    VENDOR_PATH.'/../lib'
);

$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$reader = new \Doctrine\Common\Annotations\CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
$_ENV['annotation_reader'] = $reader;
