<?php

/**
 * Class RansackQueryBuilderTest
 */
class RansackQueryBuilderTest extends PHPUnit_Framework_TestCase
{

    /**
     * verifica se gerou o DQL corretament.
     */
    public function testIncludes()
    {
        $options  = [
            'only'    => ['id', 'email'],
            'include' => [
                'person' => [
                    'only' => ['name']
                ]
            ]
        ];
        $qb = User::ransack([])->includes($options);
        $select = 'partial t.{id, email}, partial t_person.{id, name}';
        $this->assertEquals($select, implode(', ', $qb->getDQLPart('select')));
        $qb->includes([]);
        $this->assertEquals('t', implode(', ', $qb->getDQLPart('select')));
    }

}
