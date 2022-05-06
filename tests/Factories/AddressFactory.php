<?php

namespace Tests\Factories;

use Address;

class AddressFactory extends AbstractFactory
{
    public function create(array $params = []): Address
    {
        $params = $params + [
                'street' => $this->faker->streetName,
                'neighborhood' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'number' => $this->faker->numerify(),
            ];
        $address = new Address();
        foreach ($params as $k => $v) $address->$k = $v;
        $this->save($address);

        return $address;
    }
}
