<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use Paliari\Doctrine\VO\WhereParamsVO;
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
        $modelName = User::class;
        $alias = 't';
        $paramsVO = new WhereParamsVO();
        $paramsVO->where = [
            'id_order_by' => 'asc',
        ];
        $includes = [
            'only' => ['id', 'email'],
            'include' => [
                'person' => [
                    'only' => ['id', 'name'],
                    'include' => ['address'],
                ],
            ],
        ];
        $qb = EM::getEm()->createQueryBuilder()->from($modelName, $alias);
        $rows = $this->ransack->query($qb, $modelName, $alias)
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
}
