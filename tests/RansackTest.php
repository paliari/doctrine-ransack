<?php

namespace Tests;

use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\WhereParamsVO;
use PHPUnit\Framework\TestCase;

/**
 * Class RansackTest
 */
class RansackTest extends TestCase
{
    public function testDql()
    {
        $modelName = \User::class;
        $alias = 't';
        $config = new RansackConfig();
        $ransack = new Ransack($config);
        $qb = EM::getEm()->createQueryBuilder()->from($modelName, $alias);
        $paramsVO = new WhereParamsVO();
        $paramsVO->where = [
            'person_name_cont' => 'ze das cove',
            'person_address_street_eq' => 'abc',
        ];
        $rb = $ransack->query($qb, $modelName, $alias)->where($paramsVO)->includes();
        $dql = $rb->getQbManager()->qb->getDQL();
        $expected = 'SELECT t FROM User t LEFT JOIN t.person t_person LEFT JOIN t_person.address t_person_address WHERE t_person.name LIKE :t_person_name_cont AND t_person_address.street = :t_person_address_street_eq';

        $this->assertEquals($expected, $dql, 'Dql');
    }
}
