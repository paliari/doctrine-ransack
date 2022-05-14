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
