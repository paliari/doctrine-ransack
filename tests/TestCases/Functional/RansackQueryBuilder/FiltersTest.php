<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\WhereParamsVO;
use PHPUnit\Framework\TestCase;
use Tests\EM;
use Tests\Factories\AddressFactory;
use Tests\Factories\PersonFactory;
use Tests\Factories\UserFactory;
use User;

class FiltersTest extends TestCase
{
    protected AddressFactory $addressFactory;
    protected PersonFactory $personFactory;
    protected UserFactory $userFactory;
    protected Ransack $ransack;

    protected function setUp(): void
    {
        parent::setUp();
        $this->addressFactory = new AddressFactory();
        $this->personFactory = new PersonFactory();
        $this->userFactory = new UserFactory();
        $this->ransack = new Ransack(new RansackConfig());
    }

    public function testFilter()
    {
        $modelName = User::class;
        $alias = 't';
        $address1 = $this->addressFactory->create();
        $address2 = $this->addressFactory->create();
        $person1 = $this->personFactory->create([], $address1);
        $person2 = $this->personFactory->create([], $address2);
        $person3 = $this->personFactory->create([], $address2);
        $user1 = $this->userFactory->create([], $person1);
        $user2 = $this->userFactory->create([], $person2);
        $user3 = $this->userFactory->create([], $person3);
        $paramsVO = new WhereParamsVO();
        $paramsVO->where = [
            'person_address_street_eq' => $address2->street,
            'person_address_city_eq' => $address2->city,
            'id_order_by' => 'asc',
        ];
        $qb = EM::getEm()->createQueryBuilder()->from($modelName, $alias);
        $res = $this->ransack->query($qb, $modelName, $alias)
            ->where($paramsVO)
            ->includes()
            ->getQuery()
            ->getResult();
        $this->assertCount(2, $res);
        $this->assertEquals($user2, $res[0]);
        $this->assertEquals($user3, $res[1]);
        $paramsVO->where = [
            'person_address_street_not_eq' => $address2->street,
            'person_address_city_not_eq' => $address2->city,
            'id_order_by' => 'asc',
        ];
        $qb = EM::getEm()->createQueryBuilder()->from($modelName, $alias);
        $res = $this->ransack->query($qb, $modelName, $alias)
            ->where($paramsVO)
            ->includes()
            ->getQuery()
            ->getResult();
        $this->assertCount(1, $res);
        $this->assertEquals($user1, $res[0]);
    }
}
