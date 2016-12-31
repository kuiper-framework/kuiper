<?php

namespace kuiper\reflection;

interface ReflectionTypeInterface
{
    /**
     * Parses type string to type object.
     *
     * type-expression          = 1*(array-of-type-expression|array-of-type|type ["|"])
     * array-of-type-expression = "(" type-expression ")[]"
     * array-of-type            = type "[]"
     * type                     = class-name|keyword
     * class-name               = 1*CHAR
     * keyword                  = "string"|"integer"|"int"|"boolean"|"bool"|"float"
     *                            |"double"|"object"|"mixed"|"array"|"resource"
     *                            |"void"|"null"|"callback"|"false"|"true"|"self"
     *
     * @see https://phpdoc.org/docs/latest/references/phpdoc/types.html
     *
     * @param string $typeString
     *
     * @return ReflectionTypeInterface
     */
    public static function parse($typeString);

    /**
     * @return bool return true when type is an array
     */
    public function isArray();

    /**
     * @return ReflectionTypeInterface|null the value type of the array.
     *                                      return null when type is not an array
     */
    public function getArrayValueType();

    /**
     * @return bool return true when type is compound
     */
    public function isCompound();

    /**
     * @return ReflectionTypeInterface[]|null the compound types.
     *                                        return null when type is not compound
     */
    public function getCompoundTypes();

    /**
     * @return bool return true if the type is null or contain null in compound types
     */
    public function isNullable();

    /**
     * @return bool return true if the type is object
     */
    public function isObject();

    /**
     * @return string|null return the class name
     *                     return null when type is not object or class name is unknown
     */
    public function getClassName();

    /**
     * @return bool return true if the type is builtin
     */
    public function isBuiltin();

    /**
     * @return string|null return the builtin type name
     *                     return null when the type is not builtin
     */
    public function getBuiltinType();

    /**
     * @return bool return true is current type is unknown
     */
    public function isMixed();

    /**
     * @return bool return true is the type is null
     */
    public function isNull();

    /**
     * @return string
     */
    public function __toString();

    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value);

    /**
     * Sanitizes input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize($value);
}
