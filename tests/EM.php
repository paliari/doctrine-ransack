<?php

namespace Tests;

use Doctrine\ORM\EntityManager;

class EM
{
    protected static $_em;

    public static function setEm(EntityManager $em)
    {
        static::$_em = $em;
    }

    public static function getEm(): EntityManager
    {
        return static::$_em;
    }
}
