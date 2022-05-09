<?php

namespace Tests\TestCases\Unit;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Expressions\ParamFilterParser;
use PHPUnit\Framework\TestCase;

class ParamFilterParserTest extends TestCase
{
    public function testParse()
    {
        $parser = new ParamFilterParser();
        $vo = $parser->parse('person_name_eq', 'abc');
        $this->assertEquals('person_name', $vo->key);
        $this->assertEquals('eq', $vo->exprName);
        $this->assertEquals('abc', $vo->value);
    }

    public function testParseThrow()
    {
        $parser = new ParamFilterParser();
        $this->expectException(RansackException::class);
        $this->expectExceptionMessage("Condition 'column_notExpr' not found!");
        $parser->parse('column_notExpr', 'abc');
    }
}
