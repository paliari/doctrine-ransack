<?php
include __DIR__ . '/../vendor/autoload.php';
use Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager;

$params = [
    'driver' => 'pdo_sqlite',
    'memory' => true,
    'charset' => 'UTF8',
];
$config = new Configuration();
$driverImpl = $config->newDefaultAnnotationDriver(__DIR__ . '/models');
$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/../tmp/proxies');
$config->setProxyNamespace('Proxies');
$config->setAutoGenerateProxyClasses(true);
$em = EntityManager::create($params, $config);
$sqls = [
    'CREATE TABLE addresses ( id INTEGER PRIMARY KEY ASC, street TEXT, city TEXT, number TEXT)',
    'CREATE TABLE people ( id INTEGER PRIMARY KEY ASC, name TEXT, document TEXT, address_id INTEGER)',
    'CREATE INDEX ix_person_address ON people (address_id)',
    'CREATE TABLE users ( id INTEGER PRIMARY KEY ASC, email TEXT, password TEXT, person_id INTEGER)',
    'CREATE INDEX ix_user_person ON users (person_id)',
];
foreach ($sqls as $sql) {
    $res = $em->getConnection()->executeQuery($sql);
}
\Tests\EM::setEm($em);
