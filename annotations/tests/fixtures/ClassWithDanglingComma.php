<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\DummyAnnotation;

/**
 * Phalcon cannot support dangling comma
 *
 * @DummyAnnotation(dummyValue = "bar",)
 */
class ClassWithDanglingComma
{
}
