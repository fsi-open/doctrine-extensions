<?php

error_reporting(E_ALL | E_STRICT);

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));
define('FILESYSTEM1', TESTS_TEMP_DIR . '/filesystem1');
define('FILESYSTEM2', TESTS_TEMP_DIR . '/filesystem2');

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
if (!file_exists(TESTS_TEMP_DIR . '/cache')) {
    if (!mkdir(TESTS_TEMP_DIR . '/cache', 0777, true)) {
        die(sprintf('Failed to create temp cache directory for tests "%s"', TESTS_TEMP_DIR . '/cache'));
    }
}

foreach (array(FILESYSTEM1, FILESYSTEM2) as $filesystem) {
    if (!file_exists( $filesystem)) {
        if (!mkdir($filesystem, 0777, true)) {
            die(sprintf('Failed to create filesystem cache directory for tests "%s"', $filesystem));
        }
    }
}

$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->add('FSi\\DoctrineExtensions\\Tests', __DIR__);

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    VENDOR_PATH.'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'FSi\\DoctrineExtensions\\Translatable\\Mapping\\Annotation',
    VENDOR_PATH.'/../lib'
);
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'FSi\\DoctrineExtensions\\Versionable\\Mapping\\Annotation',
    VENDOR_PATH.'/../lib'
);
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'FSi\\DoctrineExtensions\\LoStorage\\Mapping\\Annotation',
    VENDOR_PATH.'/../lib'
);
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'FSi\\DoctrineExtensions\\Uploadable\\Mapping\\Annotation',
    VENDOR_PATH.'/../lib'
);

$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$reader = new \Doctrine\Common\Annotations\CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
$_ENV['annotation_reader'] = $reader;
