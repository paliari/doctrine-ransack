<?php

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    #[ORM\Column(type: 'string', length: 255)]
    public $name = '';

    #[ORM\Column(type: 'datetime')]
    public $starts_at;

    #[ORM\Column(type: 'boolean')]
    public $active = true;
}
