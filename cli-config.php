<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require_once 'tests/boot.php';
return ConsoleRunner::createHelperSet($em);
