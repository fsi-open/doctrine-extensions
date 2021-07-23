<?php

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', dirname(__DIR__) . '/vendor');
define('FILESYSTEM1', TESTS_TEMP_DIR . '/filesystem1');
define('FILESYSTEM2', TESTS_TEMP_DIR . '/filesystem2');

if (!file_exists(TESTS_TEMP_DIR . '/cache')) {
    if (!mkdir(TESTS_TEMP_DIR . '/cache', 0777, true)) {
        die(sprintf('Failed to create temp cache directory for tests "%s"', TESTS_TEMP_DIR . '/cache'));
    }
}

foreach ([FILESYSTEM1, FILESYSTEM2] as $filesystem) {
    if (!file_exists( $filesystem)) {
        if (!mkdir($filesystem, 0777, true)) {
            die(sprintf('Failed to create filesystem cache directory for tests "%s"', $filesystem));
        }
    }
}

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    VENDOR_PATH.'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'FSi\\DoctrineExtensions\\Translatable\\Mapping\\Annotation',
    VENDOR_PATH.'/../lib'
);
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    'FSi\\DoctrineExtensions\\Uploadable\\Mapping\\Annotation',
    VENDOR_PATH.'/../lib'
);

$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$_ENV['annotation_reader'] = $reader;
