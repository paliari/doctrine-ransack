<?php

/**
 * @Entity
 * @Table(name="users")
 */
class User
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
     * @Column(type="string", length=255, unique=true)
     */
    public $email = '';

    /**
     * @Column(type="string", length=40)
     */
    public $password = '';

    /**
     * @Column(type="integer", nullable=true)
     */
    public $person_id;

    /**
     * @ManyToOne(targetEntity="Person")
     * @JoinColumn(name="person_id", referencedColumnName="id")
     */
    private $person;

    /**
     * @return mixed
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param mixed $person
     */
    public function setPerson($person)
    {
        $this->person = $person;
    }
}
