<?php

namespace kuiper\annotations;

use kuiper\annotations\exception\AnnotationException;

abstract class DocUtils
{
    /**
     * @throws AnnotationException
     */
    public static function checkDocReadability()
    {
        if (extension_loaded('Zend Optimizer+')
            && (ini_get('zend_optimizerplus.save_comments') === '0'
                || ini_get('opcache.save_comments') === '0')) {
            throw AnnotationException::optimizerPlusSaveComments();
        }

        if (extension_loaded('Zend OPcache') && ini_get('opcache.save_comments') == 0) {
            throw AnnotationException::optimizerPlusSaveComments();
        }

        if (PHP_VERSION_ID < 70000) {
            if (extension_loaded('Zend Optimizer+')
                && (ini_get('zend_optimizerplus.load_comments') === '0'
                    || ini_get('opcache.load_comments') === '0')) {
                throw AnnotationException::optimizerPlusLoadComments();
            }

            if (extension_loaded('Zend OPcache') && ini_get('opcache.load_comments') == 0) {
                throw AnnotationException::optimizerPlusLoadComments();
            }
        }
    }
}
