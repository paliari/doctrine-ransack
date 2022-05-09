<?php

namespace Tests\TestCases\Unit;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Factories\ExprFactory;
use PHPUnit\Framework\TestCase;

class ExprFactoryTest extends TestCase
{
    public function testGet()
    {
        $factory = new ExprFactory();
        foreach (ExprFactory::MAP as $name => $className) {
            $class = $factory->get($name);
            $this->assertInstanceOf($className, $class);
        }
    }

    public function testThrow()
    {
        $this->expectException(RansackException::class);
        $this->expectExceptionMessage("Ransack expression 'aaa' not found!");
        $factory = new ExprFactory();
        $factory->get('aaa');
    }
}
