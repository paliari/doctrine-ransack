<?php

namespace Tests;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

class Db
{
    public static function connect(): EntityManager
    {
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
        static::migrate($em);
        EM::setEm($em);

        return $em;
    }

    protected static function migrate(EntityManager $em)
    {
        $sqls = [
            'CREATE TABLE addresses ( id INTEGER PRIMARY KEY ASC, street TEXT, city TEXT, number TEXT, neighborhood TEXT)',
            'CREATE TABLE people ( id INTEGER PRIMARY KEY ASC, name TEXT, email TEXT, document TEXT, address_id INTEGER)',
            'CREATE INDEX ix_person_address ON people (address_id)',
            'CREATE TABLE users ( id INTEGER PRIMARY KEY ASC, email TEXT, password TEXT, person_id INTEGER)',
            'CREATE INDEX ix_user_person ON users (person_id)',
        ];
        foreach ($sqls as $sql) {
            $em->getConnection()->executeQuery($sql);
        }
    }
}
