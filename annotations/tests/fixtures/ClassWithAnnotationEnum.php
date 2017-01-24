<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationEnum;

class ClassWithAnnotationEnum
{
    /**
     * @AnnotationEnum("TWO")
     */
    public function bar()
    {
    }
}
