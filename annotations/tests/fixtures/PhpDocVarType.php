<?php

namespace kuiper\annotations\fixtures;

final class PhpDocVarType extends TestPhpDocReaderParent implements TestPhpDocReaderInterface
{
    /**
     * @var mixed
     */
    public $mixed;

    /**
     * @var bool
     */
    public $boolean;

    /**
     * @var bool
     */
    public $bool;

    /**
     * @var float
     */
    public $float;

    /**
     * @var string
     */
    public $string;

    /**
     * @var int
     */
    public $integer;

    /**
     * @var array
     */
    public $array;

    /**
     * @var DummyClass
     */
    public $annotation;

    /**
     * @var int[]
     */
    public $arrayOfIntegers;

    /**
     * @var string[]
     */
    public $arrayOfStrings;

    /**
     * @var DummyClass[]
     */
    public $arrayOfAnnotations;

    /**
     * @var string|array
     */
    public $multipleType;

    /**
     * Gets the value of field1.
     *
     * @param int $integer
     *
     * @return int
     */
    public function integerMethod($integer)
    {
    }

    /**
     * Gets the value of field1.
     *
     * @param DummyClass $annot
     * @param bool       $bool
     */
    public function annotMethod($annot, $bool)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function foo($i)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function bar()
    {
    }
}
