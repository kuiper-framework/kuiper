<?php
namespace foo;

use ArrayObject;

// this is the same as use My\Full\NSname as NSname
use My\Full\Classname as Another;

// importing a global class
use My\Full\ClassnameB as AnotherB;

// importing a function (PHP 5.6+)
use My\Full\NSname;

// aliasing a function (PHP 5.6+)
use My\Full\NSnameB;

// importing a constant (PHP 5.6+)
use some\ns\ClassA;

use some\ns\ClassB;
use some\ns\ClassC as C;

// PHP 7+ code
use const My\Full\CONSTANT;
use const some\ns\ConstA;
use const some\ns\ConstB;
use const some\ns\ConstC;
use function My\Full\functionName;
use function My\Full\functionName as func;
use function some\ns\fn_a;
use function some\ns\fn_b;
use function some\ns\fn_c;
