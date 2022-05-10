<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use Paliari\Doctrine\VO\RansackParamsVO;
use Tests\CustomAssociation;
use Tests\EM;
use Tests\TestCases\Functional\BaseTestFunctional;
use User;

class CustomAssociationTest extends BaseTestFunctional
{
    public function testFilter()
    {
        $this->ransack->getConfig()->setCustomAssociation(new CustomAssociation());
        $modelName = User::class;
        $alias = 't';
        $person1 = $this->personFactory->create([]);
        $person2 = $this->personFactory->create([]);
        $user1 = $this->userFactory->create([], $person1);
        $user2 = $this->userFactory->create(['email' => $this->faker->email], $person2);
        $paramsVO = new RansackParamsVO();
        $paramsVO->where = [
            'custom_email_eq' => $person1->email,
            'id_order_by' => 'asc',
        ];
        $qb = $this->em->createQueryBuilder()->from($modelName, $alias);
        $this->assertNotEquals($user2->email, $user2->getPerson()->email);
        $res = $this->ransack->query($qb, $modelName, $alias)
            ->where($paramsVO)
            ->includes()
            ->getQuery()
            ->getResult();
        $this->assertCount(1, $res);
        $this->assertEquals($user1, $res[0]);
    }
}
