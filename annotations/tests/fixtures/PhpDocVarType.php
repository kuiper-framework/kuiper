<?php
namespace kuiper\annotations\fixtures;

final class PhpDocVarType extends TestPhpDocReaderParent implements TestPhpDocReaderInterface
{

    /**
     * @var mixed
     */
    public $mixed;

    /**
     * @var boolean
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
     * @var integer
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
     * @var array<integer>
     */
    public $arrayOfIntegers;

    /**
     * @var string[]
     */
    public $arrayOfStrings;

    /**
     * @var array<DummyClass>
     */
    public $arrayOfAnnotations;

    /**
     * @var string|array
     */
    public $multipleType;

    /**
     * Gets the value of field1.
     *
     * @param integer $integer
     * @return integer
     */
    public function integerMethod($integer)
    {
    }

    /**
     * Gets the value of field1.
     *
     * @param DummyClass $annot
     * @param bool $bool
     */
    public function annotMethod($annot, $bool)
    {
    }

    /**
     * @inheritDoc
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
