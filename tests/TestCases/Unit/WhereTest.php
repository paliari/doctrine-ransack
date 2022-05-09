<?php

namespace Tests\TestCases\Unit;

use Faker\Factory;
use Faker\Generator;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackBuilder;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\WhereParamsVO;
use PHPUnit\Framework\TestCase;
use Tests\EM;
use User;

class WhereTest extends TestCase
{
    protected Ransack $ransack;
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ransack = new Ransack(new RansackConfig());
        $this->faker = Factory::create('pt_BR');
    }

    public function testBetween()
    {
        $where = [
            'person_id_between' => [$this->faker->numberBetween(1, 100), $this->faker->numberBetween(1000, 2000)],
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id BETWEEN :t_person_id_between_x AND :t_person_id_between_y';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals(
            $where['person_id_between'][0],
            $rb->getQueryBuilder()->getParameter('t_person_id_between_x')->getValue(),
        );
        $this->assertEquals(
            $where['person_id_between'][1],
            $rb->getQueryBuilder()->getParameter('t_person_id_between_y')->getValue(),
        );
    }

    public function testCont()
    {
        $where = [
            'person_name_cont' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_cont';
        $expectedName = '%' . str_replace(' ', '%', $where['person_name_cont']) . '%';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_cont')->getValue());
    }

    public function testContWithOr()
    {
        $where = [
            'person_name_or_person_document_cont' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_cont OR t_person.document LIKE :t_person_document_cont';
        $expectedName = '%' . str_replace(' ', '%', $where['person_name_or_person_document_cont']) . '%';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_cont')->getValue());
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_document_cont')->getValue());
    }

    public function testEnd()
    {
        $where = [
            'person_name_end' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_end';
        $expectedName = '%' . $where['person_name_end'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_end')->getValue());
    }

    public function testGroupBy()
    {
        $where = [
            'person_name_group_by' => 'asc',
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person GROUP BY t_person.name';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
    }

    public function testEq()
    {
        $where = [
            'person_name_eq' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name = :t_person_name_eq';
        $expectedName = $where['person_name_eq'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_eq')->getValue());
    }

    public function testGt()
    {
        $where = [
            'person_id_gt' => 0,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id > :t_person_id_gt';
        $expectedName = $where['person_id_gt'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_id_gt')->getValue());
    }

    public function testGtEq()
    {
        $where = [
            'person_id_gteq' => 0,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id >= :t_person_id_gteq';
        $expectedName = $where['person_id_gteq'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_id_gteq')->getValue());
    }

    public function testIn()
    {
        $where = [
            'person_id_in' => [$this->faker->numberBetween(), $this->faker->numberBetween()],
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id IN(:t_person_id_in)';
        $expectedName = $where['person_id_in'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_id_in')->getValue());
    }

    public function testLteq()
    {
        $where = [
            'person_id_lteq' => $this->faker->numberBetween(),
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id <= :t_person_id_lteq';
        $expectedName = $where['person_id_lteq'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_id_lteq')->getValue());
    }

    public function testLt()
    {
        $where = [
            'person_id_lt' => $this->faker->numberBetween(),
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id < :t_person_id_lt';
        $expectedName = $where['person_id_lt'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_id_lt')->getValue());
    }

    public function testMatches()
    {
        $where = [
            'person_name_matches' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_matches';
        $expectedName = $where['person_name_matches'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_matches')->getValue());
    }

    public function testNotCont()
    {
        $where = [
            'person_name_not_cont' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name NOT LIKE :t_person_name_not_cont';
        $expectedName = '%' . str_replace(' ', '%', $where['person_name_not_cont']) . '%';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_not_cont')->getValue());
    }

    public function testNotEnd()
    {
        $where = [
            'person_name_not_end' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name NOT LIKE :t_person_name_not_end';
        $expectedName = '%' . $where['person_name_not_end'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_not_end')->getValue());
    }

    public function testNotEq()
    {
        $where = [
            'person_name_not_eq' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name <> :t_person_name_not_eq';
        $expectedName = $where['person_name_not_eq'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_not_eq')->getValue());
    }

    public function testNotIn()
    {
        $where = [
            'person_id_not_in' => [$this->faker->numberBetween(), $this->faker->numberBetween()],
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id NOT IN(:t_person_id_not_in)';
        $expectedName = $where['person_id_not_in'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_id_not_in')->getValue());
    }

    public function testNotMatches()
    {
        $where = [
            'person_name_not_matches' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name NOT LIKE :t_person_name_not_matches';
        $expectedName = $where['person_name_not_matches'];
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_not_matches')->getValue());
    }

    public function testNotNull()
    {
        $where = [
            'person_id_not_null' => [$this->faker->numberBetween(), $this->faker->numberBetween()],
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id IS NOT NULL';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
    }

    public function testNotStart()
    {
        $where = [
            'person_name_not_start' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name NOT LIKE :t_person_name_not_start';
        $expectedName = $where['person_name_not_start'] . '%';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_not_start')->getValue());
    }

    public function testNull()
    {
        $where = [
            'person_id_null' => null,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t WHERE t.person_id IS NULL';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
    }

    public function testOrderBy()
    {
        $where = [
            'person_name_order_by' => 'ASC',
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person ORDER BY t_person.name ASC';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
    }

    public function testStart()
    {
        $where = [
            'person_name_start' => $this->faker->name,
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_start';
        $expectedName = $where['person_name_start'] . '%';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($expectedName, $rb->getQueryBuilder()->getParameter('t_person_name_start')->getValue());
    }

    public function testOr()
    {
        $where = [
            'person.name_start' => $this->faker->name,
            'or' => [
                'email_eq' => $this->faker->email,
                'person.document_eq' => $this->faker->cpf(),
            ],
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_start AND (t.email = :t_email_eq OR t_person.document = :t_person_document_eq)';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($where['person.name_start'] . '%', $rb->getQueryBuilder()->getParameter('t_person_name_start')->getValue());
        $this->assertEquals($where['or']['email_eq'], $rb->getQueryBuilder()->getParameter('t_email_eq')->getValue());
        $this->assertEquals($where['or']['person.document_eq'], $rb->getQueryBuilder()->getParameter('t_person_document_eq')->getValue());
    }

    public function testAnd()
    {
        $where = [
            'person.name_start' => $this->faker->name,
            'and' => [
                'email_eq' => $this->faker->email,
                'person.document_eq' => $this->faker->cpf(),
            ],
        ];
        $rb = $this->newRansackBuilder($where);
        $expectedDql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t_person.name LIKE :t_person_name_start AND (t.email = :t_email_eq AND t_person.document = :t_person_document_eq)';
        $dql = $rb->getQueryBuilder()->getDQL();
        $this->assertEquals($expectedDql, $dql);
        $this->assertEquals($where['person.name_start'] . '%', $rb->getQueryBuilder()->getParameter('t_person_name_start')->getValue());
        $this->assertEquals($where['and']['email_eq'], $rb->getQueryBuilder()->getParameter('t_email_eq')->getValue());
        $this->assertEquals($where['and']['person.document_eq'], $rb->getQueryBuilder()->getParameter('t_person_document_eq')->getValue());
    }

    public function testThrowPoint()
    {
        $this->expectException(RansackException::class);
        $this->expectExceptionMessage("Target Model 'a' not found!");
        $where = ['a.b_eq' => 1];
        $this->newRansackBuilder($where);
    }

    public function testThrowUnderline()
    {
        $this->expectException(RansackException::class);
        $this->expectExceptionMessage("Field 'a_b' not found!");
        $where = ['a_b_eq' => 1];
        $this->newRansackBuilder($where);
    }

    protected function newRansackBuilder(array $where): RansackBuilder
    {
        $modelName = User::class;
        $alias = 't';
        $qb = EM::getEm()->createQueryBuilder()->from($modelName, $alias);
        $paramsVO = new WhereParamsVO();
        $paramsVO->where = $where;

        return $this->ransack->query($qb, $modelName, $alias)->where($paramsVO)->includes();
    }
}
