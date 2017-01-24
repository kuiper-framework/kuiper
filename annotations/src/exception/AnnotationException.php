<?php

namespace kuiper\annotations\exception;

use RuntimeException;

class AnnotationException extends RuntimeException
{
    /**
     * @return AnnotationException
     */
    public static function optimizerPlusSaveComments()
    {
        return new self(
            'You have to enable opcache.save_comments=1 or zend_optimizerplus.save_comments=1.'
        );
    }

    /**
     * @return AnnotationException
     */
    public static function optimizerPlusLoadComments()
    {
        return new self(
            'You have to enable opcache.load_comments=1 or zend_optimizerplus.load_comments=1.'
        );
    }
}
