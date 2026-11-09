<?php

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'addresses')]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    #[ORM\Column(type: 'string', length: 255)]
    public $street = '';

    #[ORM\Column(type: 'string', length: 255)]
    public $neighborhood = '';

    #[ORM\Column(type: 'string', length: 255)]
    public $city = '';

    #[ORM\Column(type: 'string', length: 255)]
    public $number = '';
}
