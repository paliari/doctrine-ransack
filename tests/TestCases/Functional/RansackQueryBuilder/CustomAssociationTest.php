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
        $user2 = $this->userFactory->create(['email' => $this->faker->email()], $person2);
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

    public function testFilterPoint()
    {
        $person1 = $this->personFactory->create();
        $person2 = $this->personFactory->create();
        $user1 = $this->userFactory->create([], $person1);
        $this->userFactory->create(['email' => $this->faker->email()], $person2);
        $this->assertEquals([$user1->id], $this->searchIds(User::class, ['custom.email_eq' => $person1->email]));
        $this->assertEquals([$user1->id], $this->searchIds(User::class, ['custom.name_eq' => $person1->name]));
    }

    public function testIncludesCustom()
    {
        $person = $this->personFactory->create();
        $user1 = $this->userFactory->create([], $person);
        $user2 = $this->userFactory->create();
        $paramsVO = new RansackParamsVO();
        $paramsVO->where = ['id_order_by' => 'asc'];
        $includes = [
            'only' => ['email'],
            'include' => ['custom' => ['only' => ['name']]],
        ];
        $qb = $this->em->createQueryBuilder()->from(User::class, 't');
        $rb = $this->ransack->query($qb, User::class, 't')->where($paramsVO)->includes($includes);
        $expectedDql = 'SELECT partial t.{id, email}, partial t_custom.{id, name} FROM User t'
            . ' LEFT JOIN Person t_custom WITH t.email = t_custom.email ORDER BY t.id asc';
        $this->assertEquals($expectedDql, $rb->getQueryBuilder()->getDQL());
        $expected = [
            ['id' => $user1->id, 'email' => $user1->email],
            ['id' => $person->id, 'name' => $person->name],
            ['id' => $user2->id, 'email' => $user2->email],
            null,
        ];
        $this->assertEquals($expected, $rb->getQuery()->getArrayResult());
    }
}
