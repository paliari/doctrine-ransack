<?php
include __DIR__ . '/../vendor/autoload.php';

\Doctrine\Deprecations\Deprecation::enableWithTriggerError();

$em = \Tests\Db::connect();
