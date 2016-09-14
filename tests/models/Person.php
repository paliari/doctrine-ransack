<?php

/**
 * @Entity
 * @Table(name="people")
 */
class Person extends AbstractModel
{

    /**
     * Primary Key column.
     *
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string", length=250)
     */
    public $name = '';

}
