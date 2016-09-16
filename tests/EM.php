<?php

class EM
{
    protected static $_em;

    public static function setEm($em)
    {
        static::$_em = $em;
    }

    public static function getEm()
    {
        return static::$_em;
    }

}
