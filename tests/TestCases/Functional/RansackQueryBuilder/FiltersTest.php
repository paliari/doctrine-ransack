<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use Paliari\Doctrine\VO\RansackParamsVO;
use Tests\TestCases\Functional\BaseTestFunctional;
use User;

class FiltersTest extends BaseTestFunctional
{
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
        $paramsVO = new RansackParamsVO();
        $paramsVO->where = [
            'person_address_street_eq' => $address2->street,
            'person_address_city_eq' => $address2->city,
            'id_order_by' => 'asc',
        ];
        $qb = $this->em->createQueryBuilder()->from($modelName, $alias);
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
        $qb = $this->em->createQueryBuilder()->from($modelName, $alias);
        $res = $this->ransack->query($qb, $modelName, $alias)
            ->where($paramsVO)
            ->includes()
            ->getQuery()
            ->getResult();
        $this->assertCount(1, $res);
        $this->assertEquals($user1, $res[0]);
    }
}
