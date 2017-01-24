<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationEnum;

class ClassWithInvalidAnnotationEnum
{
    /**
     * @AnnotationEnum("THREE")
     */
    public function bar()
    {
    }
}
