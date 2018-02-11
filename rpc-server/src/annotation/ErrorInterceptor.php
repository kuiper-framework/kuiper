<?php

namespace kuiper\rpc\server\annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class ErrorInterceptor
{
    /**
     * @var string
     * @Required
     */
    public $class;
}
