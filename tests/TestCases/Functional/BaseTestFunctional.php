<?php

namespace Tests\TestCases\Functional;

use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Faker\Generator;
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use PHPUnit\Framework\TestCase;
use Tests\Db;
use Tests\EM;
use Tests\Factories\AddressFactory;
use Tests\Factories\PersonFactory;
use Tests\Factories\UserFactory;

class BaseTestFunctional extends TestCase
{
    protected AddressFactory $addressFactory;
    protected PersonFactory $personFactory;
    protected UserFactory $userFactory;
    protected Ransack $ransack;
    protected Generator $faker;
    protected EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = Db::connect();
        $this->addressFactory = new AddressFactory();
        $this->personFactory = new PersonFactory();
        $this->userFactory = new UserFactory();
        $this->ransack = new Ransack(new RansackConfig(EM::getEm()));
        $this->faker = Factory::create('pt_BR');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $sqls = [
            'DELETE FROM addresses',
            'DELETE FROM people',
            'DELETE FROM users',
        ];
        foreach ($sqls as $sql) {
            EM::getEm()->getConnection()->executeQuery($sql);
        }
    }
}
