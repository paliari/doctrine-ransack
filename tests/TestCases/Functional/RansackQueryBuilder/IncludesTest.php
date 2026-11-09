<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\VO\RansackParamsVO;
use Tests\EM;
use Tests\TestCases\Functional\BaseTestFunctional;
use User;

class IncludesTest extends BaseTestFunctional
{
    public function testIncludes()
    {
        /** @var User[] $users */
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = $this->userFactory->create(
                person: $this->personFactory->create(
                    address: $this->addressFactory->create()
                )
            );
        }
        $entityName = User::class;
        $alias = 't';
        $paramsVO = new RansackParamsVO();
        $paramsVO->where = [
            'id_order_by' => 'asc',
        ];
        $includes = [
            'only' => ['id', 'email'],
            'include' => [
                'person' => [
                    'only' => ['name'],
                    'include' => ['address'],
                ],
            ],
        ];
        $qb = EM::getEm()->createQueryBuilder()->from($entityName, $alias);
        $rows = $this->ransack->query($qb, $entityName, $alias)
            ->where($paramsVO)
            ->includes($includes)
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $i => $row) {
            $user = $users[$i];
            $expected = [
                'id' => $user->id,
                'email' => $user->email,
                'person' => [
                    'id' => $user->getPerson()->id,
                    'name' => $user->getPerson()->name,
                    'address' => [
                        'id' => $user->getPerson()->getAddress()->id,
                        'street' => $user->getPerson()->getAddress()->street,
                        'neighborhood' => $user->getPerson()->getAddress()->neighborhood,
                        'city' => $user->getPerson()->getAddress()->city,
                        'number' => $user->getPerson()->getAddress()->number,
                    ],
                ],
            ];
            $this->assertEquals($expected, $row);
        }
    }

    public function testWhereAndIncludesSameRelationJoinOnce()
    {
        $person1 = $this->personFactory->create(['name' => 'Ana Silva']);
        $person2 = $this->personFactory->create(['name' => 'Bruno Souza']);
        $user1 = $this->userFactory->create([], $person1);
        $this->userFactory->create([], $person2);
        $paramsVO = new RansackParamsVO();
        $paramsVO->where = ['person.name_cont' => 'Silva'];
        $includes = [
            'only' => ['email'],
            'include' => ['person' => ['only' => ['name']]],
        ];
        $qb = EM::getEm()->createQueryBuilder()->from(User::class, 't');
        $rb = $this->ransack->query($qb, User::class, 't')
            ->includes()
            ->where($paramsVO)
            ->includes($includes);
        $expectedDql = 'SELECT partial t.{id, email}, partial t_person.{id, name} FROM User t'
            . ' LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_cont';
        $this->assertEquals($expectedDql, $rb->getQueryBuilder()->getDQL());
        $expected = [
            [
                'id' => $user1->id,
                'email' => $user1->email,
                'person' => ['id' => $person1->id, 'name' => $person1->name],
            ],
        ];
        $this->assertEquals($expected, $rb->getQuery()->getArrayResult());
    }

    public function testIncludesRansackException()
    {
        $this->expectException(RansackException::class);
        $this->expectExceptionMessage("Relation 'no_relation' not found!");
        $entityName = User::class;
        $alias = 't';
        $paramsVO = new RansackParamsVO();
        $includes = [
            'only' => ['id', 'email'],
            'include' => [
                'no_relation' => ['only' => ['name']],
            ],
        ];
        $qb = EM::getEm()->createQueryBuilder()->from($entityName, $alias);
        $this->ransack->query($qb, $entityName, $alias)
            ->where($paramsVO)
            ->includes($includes)
            ->getQuery()
            ->getArrayResult();
    }
}
