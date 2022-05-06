<?php

namespace Tests\Factories;

use Address;
use Person;

class PersonFactory extends AbstractFactory
{
    public function create(array $params = [], ?Address $address = null): Person
    {
        $params = $params + [
                'name' => $this->faker->name,
                'email' => $this->faker->email,
                'document' => $this->faker->cpf(),
            ];
        $person = new Person();
        foreach ($params as $k => $v) $person->$k = $v;
        $person->setAddress($address);
        $this->save($person);

        return $person;
    }
}
