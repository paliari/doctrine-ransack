<?php
include __DIR__ . '/../' . (getenv('COMPOSER_VENDOR_DIR') ?: 'vendor') . '/autoload.php';

\Doctrine\Deprecations\Deprecation::enableWithTriggerError();

$em = \Tests\Db::connect();
