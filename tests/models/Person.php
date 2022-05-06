<?php

/**
 * @Entity
 * @Table(name="people")
 */
class Person
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

    /**
     * @Column(type="string", length=255, unique=true)
     */
    public $email = '';

    /**
     * @Column(type="string", length=250)
     */
    public $document = '';

    /**
     * @Column(type="integer", nullable=true)
     */
    public $address_id;

    /**
     * @ManyToOne(targetEntity="Address")
     * @JoinColumn(name="address_id", referencedColumnName="id")
     */
    private $address;

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param Address $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }
}
