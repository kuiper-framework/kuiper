<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class HttpClient implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $client;

    /**
     * @var string
     */
    public $responseParser;
}
