<?php

/**
 * @Entity
 * @Table(name="addresses")
 */
class Address
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

    /**
     * @Column(type="string", length=255)
     */
    public $neighborhood = '';

    /**
     * @Column(type="string", length=255)
     */
    public $city = '';

    /**
     * @Column(type="string", length=255)
     */
    public $number = '';
}
