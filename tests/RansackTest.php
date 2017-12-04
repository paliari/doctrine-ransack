<?php

/**
 * Class RansackTest
 */
class RansackTest extends \PHPUnit\Framework\TestCase
{

    /**
     * verifica se gerou o DQL corretament.
     */
    public function testDQL()
    {
        $q        = [
            'id_lteq'              => 20,
            'person_name_not_null' => null,
            'person_name_eq'       => 'abc',
        ];
        $expected = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t.id <= :t_id_lteq AND t_person.name ' .
            'IS NOT NULL AND t_person.name = :t_person_name_eq';
        $this->assertEquals($expected, User::ransack($q)->getDQL());
    }

    /**
     * verifica se gerou os parametros corretamente.
     */
    public function testParams()
    {
        $q  = [
            'id_lteq'        => 20,
            'person_name_eq' => 'abc',
        ];
        $qb = User::ransack($q);
        $this->assertEquals(count($q), $qb->getParameters()->count());
        foreach ($qb->getParameters() as $k => $v) {
            $this->assertEquals('t_' . array_keys($q)[$k], $v->getName());
            $this->assertEquals($q[ltrim($v->getName(), 't_')], $v->getValue());
        }
    }

    /**
     * verifica se passou field incorreto.
     *
     * @expectedException DomainException
     * @expectedExceptionMessage Field 'aaaa' not found!
     */
    public function testException()
    {
        $q = [
            'aaaa_eq' => 'abc',
        ];
        User::ransack($q);
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage Condition 'id_eqaa' not found!
     */
    public function testCondicionException()
    {
        $q = [
            'id_eqaa' => 'abc',
        ];
        User::ransack($q);
    }

    /**
     * verifica se gerou o DQL com o OR corretamente e setou os parametros.
     */
    public function testOR()
    {
        $q   = [
            'id_lteq'                 => 20,
            'email_or_person_name_eq' => 'abc',
        ];
        $dql = 'SELECT t FROM User t LEFT JOIN t.person t_person WHERE t.id <= :t_id_lteq AND ' .
            '(t.email = :t_email_eq OR t_person.name = :t_person_name_eq)';
        $qb  = User::ransack($q);
        $this->assertEquals($dql, $qb->getDQL());
        $this->assertEquals(3, $qb->getParameters()->count());
        $this->assertNotNull($qb->getParameter('t_id_lteq'));
        $this->assertNotNull($qb->getParameter('t_email_eq'));
        $this->assertNotNull($qb->getParameter('t_person_name_eq'));
    }

    /**
     * verifica se todas expr esta gerando o DQL corretamente.
     */
    public function testAllExpr()
    {
        $rs = new \Paliari\Doctrine\Ransack();
        $r  = new \ReflectionClass($rs);
        $p  = $r->getProperty('expr');
        $p->setAccessible(true);
        $expr  = $p->getValue($rs);
        $q     = [];
        $count = 0;
        $qb    = User::ransack($q);
        foreach ($expr as $k => $v) {
            $q["email_$k"] = '1';
            if ('order_by' != $k && substr($k, -4) != 'null') {
                $count++;
            }
            if ('order_by' != $k) {
                $this->assertNotFalse(method_exists($qb->expr(), $v));
            }
        }
        $qb = User::ransack($q);
        $this->assertEquals($count, $qb->getParameters()->count());
        $this->assertEquals(count($q), substr_count($qb->getDQL(), 't.email'));
    }

    public function testBlank()
    {
        $rs = new \Paliari\Doctrine\Ransack();
        $r  = new \ReflectionClass($rs);
        $m  = $r->getMethod('blank');
        $m->setAccessible(true);
        foreach ([null, '', []] as $v) {
            $this->assertTrue($m->invokeArgs($rs, [$v]));
        }
        foreach ([0, '0', ' ', '1', 1, -1, true] as $v) {
            $this->assertFalse($m->invokeArgs($rs, [$v]));
        }
    }

    public function testExtractFksPoint()
    {
        $rs = new \Paliari\Doctrine\Ransack();
        $r  = new \ReflectionClass($rs);
        $m  = $r->getMethod('extractFksPoint');
        $m->setAccessible(true);
        $p = $r->getProperty('model');
        $p->setAccessible(true);
        $p->setValue($rs, 'User');
        $qb = $r->getProperty('qb');
        $qb->setAccessible(true);
        $qb->setValue($rs, \Paliari\Doctrine\RansackQueryBuilder::create(EM::getEm(), 'User'));
        $this->assertEquals([['person'], 'name', 'string'], $m->invokeArgs($rs, ['person.name']));
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage Target Model 'aaa' not found!
     */
    public function testExtractFksPointException()
    {
        $rs = new \Paliari\Doctrine\Ransack();
        $r  = new \ReflectionClass($rs);
        $m  = $r->getMethod('extractFksPoint');
        $m->setAccessible(true);
        $p = $r->getProperty('model');
        $p->setAccessible(true);
        $p->setValue($rs, 'User');
        $qb = $r->getProperty('qb');
        $qb->setAccessible(true);
        $qb->setValue($rs, \Paliari\Doctrine\RansackQueryBuilder::create(EM::getEm(), 'User'));
        $m->invokeArgs($rs, ['person.aaa.aa']);
    }

    public function testExtractFksUnderline()
    {
        $rs = new \Paliari\Doctrine\Ransack();
        $r  = new \ReflectionClass($rs);
        $m  = $r->getMethod('extractFksUnderline');
        $m->setAccessible(true);
        $p = $r->getProperty('model');
        $p->setAccessible(true);
        $p->setValue($rs, 'User');

        $qb = $r->getProperty('qb');
        $qb->setAccessible(true);
        $qb->setValue($rs, \Paliari\Doctrine\RansackQueryBuilder::create(EM::getEm(), 'User'));

        $this->assertEquals([['person', 'address'], 'street', 'string'], $m->invokeArgs($rs, ['person_address_street']));
    }

}
