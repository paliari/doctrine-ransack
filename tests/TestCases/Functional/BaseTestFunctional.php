<?php

namespace Tests\TestCases\Functional;

use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Faker\Generator;
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\RansackParamsVO;
use PHPUnit\Framework\TestCase;
use Tests\Db;
use Tests\EM;
use Tests\Factories\AddressFactory;
use Tests\Factories\EventFactory;
use Tests\Factories\PersonFactory;
use Tests\Factories\UserFactory;

class BaseTestFunctional extends TestCase
{
    protected AddressFactory $addressFactory;
    protected PersonFactory $personFactory;
    protected UserFactory $userFactory;
    protected EventFactory $eventFactory;
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
        $this->eventFactory = new EventFactory();
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
            'DELETE FROM events',
        ];
        foreach ($sqls as $sql) {
            EM::getEm()->getConnection()->executeStatement($sql);
        }
    }

    /**
     * @return int[] ids of the matched rows, ordered by id
     */
    protected function searchIds(string $entityName, array $where): array
    {
        $paramsVO = new RansackParamsVO();
        $paramsVO->where = $where + ['id_order_by' => 'asc'];
        $qb = $this->em->createQueryBuilder()->from($entityName, 't');
        $rows = $this->ransack->query($qb, $entityName, 't')
            ->where($paramsVO)
            ->includes(['only' => ['id']])
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }
}
