<?php

namespace Tests\Factories;

use Person;
use User;

class UserFactory extends AbstractFactory
{
    public function create(array $params = [], ?Person $person = null): User
    {
        $params = $params + [
                'email' => $person?->email ?? $this->faker->email,
                'password' => $this->faker->password,
            ];
        $user = new User();
        foreach ($params as $k => $v) $user->$k = $v;
        $user->setPerson($person);
        $this->save($user);

        return $user;
    }
}
