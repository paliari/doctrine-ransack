<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use DateTime;
use Event;
use Tests\TestCases\Functional\BaseTestFunctional;

class TypesTest extends BaseTestFunctional
{
    /**
     * @var Event[]
     */
    protected array $events;

    protected function setUp(): void
    {
        parent::setUp();
        $this->events = [
            $this->eventFactory->create(['starts_at' => new DateTime('2026-01-10 10:00:00'), 'active' => true]),
            $this->eventFactory->create(['starts_at' => new DateTime('2026-02-10 10:00:00'), 'active' => false]),
            $this->eventFactory->create(['starts_at' => new DateTime('2026-03-10 10:00:00'), 'active' => true]),
        ];
    }

    public function testDateTimeGtEq()
    {
        $ids = $this->searchIds(Event::class, ['starts_at_gteq' => new DateTime('2026-02-01')]);
        $this->assertEquals([$this->events[1]->id, $this->events[2]->id], $ids);
    }

    public function testDateTimeBetween()
    {
        $ids = $this->searchIds(Event::class, [
            'starts_at_between' => [new DateTime('2026-01-01'), new DateTime('2026-02-28')],
        ]);
        $this->assertEquals([$this->events[0]->id, $this->events[1]->id], $ids);
    }

    public function testDateTimeIn()
    {
        $ids = $this->searchIds(Event::class, [
            'starts_at_in' => [new DateTime('2026-01-10 10:00:00'), new DateTime('2026-03-10 10:00:00')],
        ]);
        $this->assertEquals([$this->events[0]->id, $this->events[2]->id], $ids);
    }

    public function testBooleanFalse()
    {
        $ids = $this->searchIds(Event::class, ['active_eq' => false]);
        $this->assertEquals([$this->events[1]->id], $ids);
    }

    public function testBooleanTrue()
    {
        $ids = $this->searchIds(Event::class, ['active_eq' => true]);
        $this->assertEquals([$this->events[0]->id, $this->events[2]->id], $ids);
    }
}
