<?php
include __DIR__ . '/../vendor/autoload.php';
use Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager;

foreach (glob(__DIR__ . '/models/*.php') as $file) {
    include_once "$file";
}
include_once __DIR__ . '/EM.php';

$params     = [
    'driver'  => 'pdo_sqlite',
    'path'    => 'tmp/test.db',
    'memory'  => true,
    'charset' => 'UTF8',
];
$config     = new Configuration();
$driverImpl = $config->newDefaultAnnotationDriver(__DIR__ . '/models');
$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/../tmp/proxies');
$config->setProxyNamespace('Proxies');
$config->setAutoGenerateProxyClasses(true);
$em = EntityManager::create($params, $config);
EM::setEm($em);
