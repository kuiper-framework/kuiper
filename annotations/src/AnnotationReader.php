<?php

namespace kuiper\annotations;

use Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotationReader extends \Doctrine\Common\Annotations\AnnotationReader implements AnnotationReaderInterface
{
    /**
     * @var AnnotationReaderInterface
     */
    private static $INSTANCE;

    public static function getInstance(): AnnotationReaderInterface
    {
        if (!self::$INSTANCE) {
            AnnotationRegistry::registerLoader('class_exists');
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }
}
