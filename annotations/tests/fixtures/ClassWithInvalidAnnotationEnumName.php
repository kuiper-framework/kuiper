<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationEnum;

class ClassWithInvalidAnnotationEnumName
{
    /**
     * @AnnotationEnum("FOUR")
     */
    public function bar()
    {
    }
}
