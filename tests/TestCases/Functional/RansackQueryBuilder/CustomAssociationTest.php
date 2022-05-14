<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\RansackParamsVO;
use Tests\CustomAssociation;
use Tests\EM;
use Tests\TestCases\Functional\BaseTestFunctional;
use User;

class CustomAssociationTest extends BaseTestFunctional
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ransack = new Ransack(new RansackConfig(EM::getEm(), new CustomAssociation()));
    }

    public function testFilter()
    {
        $entityName = User::class;
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
        $qb = $this->em->createQueryBuilder()->from($entityName, $alias);
        $this->assertNotEquals($user2->email, $user2->getPerson()->email);
        $res = $this->ransack->query($qb, $entityName, $alias)
            ->where($paramsVO)
            ->includes()
            ->getQuery()
            ->getResult();
        $this->assertCount(1, $res);
        $this->assertEquals($user1, $res[0]);
    }
}
