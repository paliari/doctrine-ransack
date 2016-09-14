<?php
include __DIR__ . '/../vendor/autoload.php';
use Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager;

foreach (glob(__DIR__ . '/models/*.php') as $file) {
    include_once "$file";
}

$params     = [
    'driver'        => 'pdo_mysql',
    'host'          => '127.0.0.1',
    'dbname'        => 'foo',
    'user'          => 'root',
    'password'      => '',
    'service'       => true,
    'charset'       => 'UTF8',
    'driverOptions' => ['charset' => 'UTF8'],
];
$config     = new Configuration();
$driverImpl = $config->newDefaultAnnotationDriver(__DIR__ . '/models');
$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/../tmp/proxies');
$config->setProxyNamespace('Proxies');
$config->setAutoGenerateProxyClasses(true);
$em = EntityManager::create($params, $config);
\Paliari\Doctrine\Ransack::instance()->setEm($em);
