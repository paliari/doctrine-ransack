<?php

namespace Tests\Factories;

use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Faker\Generator;
use Tests\EM;

abstract class AbstractFactory
{
    protected Generator $faker;
    protected EntityManager $em;

    public function __construct()
    {
        $this->faker = Factory::create('pt_BR');
        $this->em = EM::getEm();
    }

    protected function save($model)
    {
        $this->em->persist($model);
        $this->em->flush($model);
    }
}
