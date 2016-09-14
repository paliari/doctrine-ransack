<?php

/**
 * @Entity
 * @Table(name="pessoa")
 */
class Pessoa
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
