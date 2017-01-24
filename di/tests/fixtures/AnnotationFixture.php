<?php

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Inject;

class AnnotationFixture
{
    /**
     * @Inject("foo")
     */
    protected $property1;

    /**
     * @Inject
     *
     * @var AnnotationFixture2
     */
    protected $property2;

    /**
     * @Inject(name="foo")
     */
    protected $property3;

    protected $unannotatedProperty;

    /**
     * Static property shouldn't be injected.
     *
     * @Inject("foo")
     */
    protected static $staticProperty;

    public static $PARAMS;

    /**
     * @Inject({"foo", "bar"})
     */
    public function __construct($param1, $param2)
    {
        self::call(__METHOD__, func_get_args());
    }

    /**
     * @Inject
     */
    public function method1()
    {
        self::call(__METHOD__, func_get_args());
    }

    /**
     * @Inject({"foo", "bar"})
     */
    public function method2($param1, $param2)
    {
        self::call(__METHOD__, func_get_args());
    }

    /**
     * @Inject
     *
     * @param $param1
     * @param AnnotationFixture2 $param2
     */
    public function method3(AnnotationFixture2 $param1, $param2)
    {
        self::call(__METHOD__, func_get_args());
    }

    /**
     * @Inject({"foo", "bar"})
     *
     * @param AnnotationFixture2 $param1
     * @param AnnotationFixture2 $param2
     */
    public function method4($param1, $param2)
    {
        self::call(__METHOD__, func_get_args());
    }

    public function unannotatedMethod()
    {
        self::call(__METHOD__, func_get_args());
    }

    /**
     * @Inject({"bim"})
     */
    public function optionalParameter(\stdClass $optional1 = null, \stdClass $optional2 = null)
    {
        self::call(__METHOD__, func_get_args());
    }

    /**
     * @Inject
     */
    public static function staticMethod()
    {
        self::call(__METHOD__, func_get_args());
    }

    public static function call($method, $args)
    {
        self::$PARAMS[] = [$method, $args];
    }
}
