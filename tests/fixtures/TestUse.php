<?php
namespace foo;
use My\Full\Classname as Another;

// this is the same as use My\Full\NSname as NSname
use My\Full\NSname;

// importing a global class
use ArrayObject;

// importing a function (PHP 5.6+)
use function My\Full\functionName;

// aliasing a function (PHP 5.6+)
use function My\Full\functionName as func;

// importing a constant (PHP 5.6+)
use const My\Full\CONSTANT;

use My\Full\ClassnameB as AnotherB, My\Full\NSnameB;

// PHP 7+ code
use some\ns\{ClassA, ClassB, ClassC as C};
use function some\ns\{fn_a, fn_b, fn_c};
use const some\ns\{ConstA, ConstB, ConstC};
