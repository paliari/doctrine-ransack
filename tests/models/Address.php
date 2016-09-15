<?php

/**
 * @Entity
 * @Table(name="addresses")
 */
class Address extends AbstractModel
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
     * @Column(type="string", length=255)
     */
    public $street = '';

}
