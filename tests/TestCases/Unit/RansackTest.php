<?php

namespace Tests\TestCases\Unit;

use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackBuilder;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\WhereOrderByVO;
use Paliari\Doctrine\VO\WhereParamsVO;
use PHPUnit\Framework\TestCase;
use Tests\EM;
use User;

class RansackTest extends TestCase
{
    protected Ransack $ransack;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ransack = new Ransack(new RansackConfig());
    }

    public function testDql()
    {
        $paramsVO = new WhereParamsVO();
        $paramsVO->where = [
            'person_name_cont' => 'ze das cove',
            'person_address_street_eq' => 'abc',
        ];
        $rb = $this->newRansackBuilder($paramsVO);
        $dql = $rb->getQueryBuilder()->getDQL();
        $sql = $rb->getQueryBuilder()->getQuery()->getSQL();
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person LEFT JOIN t_person.address t_person_address WHERE t_person.name LIKE :t_person_name_cont AND t_person_address.street = :t_person_address_street_eq';
        $expectedSql = 'SELECT u0_.id AS id_0, u0_.email AS email_1, u0_.password AS password_2, u0_.person_id AS person_id_3, u0_.person_id AS person_id_4 FROM users u0_ LEFT JOIN people p1_ ON u0_.person_id = p1_.id LEFT JOIN addresses a2_ ON p1_.address_id = a2_.id WHERE p1_.name LIKE ? AND a2_.street = ?';
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedSql, $sql);
        $this->assertEquals(
            $paramsVO->where['person_address_street_eq'],
            $rb->getQueryBuilder()->getParameter('t_person_address_street_eq')->getValue(),
        );
        $this->assertEquals(
            '%ze%das%cove%',
            $rb->getQueryBuilder()->getParameter('t_person_name_cont')->getValue(),
        );
    }

    public function testOrderBy()
    {
        $paramsVO = new WhereParamsVO();
        $paramsVO->orderBy = [
            new WhereOrderByVO(['field' => 'person.name', 'order' => 'ASC']),
            new WhereOrderByVO(['field' => 'person.id', 'order' => 'DESC']),
        ];
        $rb = $this->newRansackBuilder($paramsVO);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person ORDER BY t_person.name ASC, t_person.id DESC';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
    }

    public function testGroupBy()
    {
        $paramsVO = new WhereParamsVO();
        $paramsVO->groupBy = [
            'person.name',
            'person.address_id',
        ];
        $rb = $this->newRansackBuilder($paramsVO);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person GROUP BY t_person.name, t_person.address_id';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
    }

    protected function newRansackBuilder(WhereParamsVO $paramsVO): RansackBuilder
    {
        $modelName = User::class;
        $alias = 't';
        $qb = EM::getEm()->createQueryBuilder()->from($modelName, $alias);

        return $this->ransack->query($qb, $modelName, $alias)->where($paramsVO)->includes();
    }
}
