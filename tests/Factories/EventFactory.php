<?php

namespace Tests\Factories;

use Event;

class EventFactory extends AbstractFactory
{
    public function create(array $params = []): Event
    {
        $params = $params + [
                'name' => $this->faker->sentence(3),
                'starts_at' => $this->faker->dateTime(),
                'active' => $this->faker->boolean(),
            ];
        $event = new Event();
        foreach ($params as $k => $v) $event->$k = $v;
        $this->save($event);

        return $event;
    }
}
