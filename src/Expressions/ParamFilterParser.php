<?php

namespace Paliari\Doctrine\Expressions;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Factories\ExprFactory;
use Paliari\Doctrine\VO\ParamFilterVO;

class ParamFilterParser
{
    protected string $pattern;

    public function __construct()
    {
        $this->pattern = '!([\w\.]+?)_(' . implode('|', array_keys(ExprFactory::MAP)) . ')$!';
    }

    /**
     * @throws RansackException
     */
    public function parse(string $key, $value): ParamFilterVO
    {
        if (preg_match($this->pattern, $key, $match)) {
            $vo = new ParamFilterVO();
            $vo->key = $match[1] ?? '';
            $vo->exprName = $match[2] ?? '';
            $vo->value = $value;

            return $vo;
        }
        throw new RansackException("Condition '$key' not found!");
    }
}
