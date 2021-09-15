<?php

declare(strict_types=1);

namespace kuiper\tars\fixtures;

use kuiper\tars\annotation\TarsProperty;

final class Request
{
    /**
     * @TarsProperty(order=0, required=true, type="int")
     *
     * @var int|null
     */
    public $intRequired;

    /**
     * @TarsProperty(order=1, required=true, type="bool")
     *
     * @var bool|null
     */
    public $boolRequired;

    /**
     * @TarsProperty(order=2, required=false, type="bool")
     *
     * @var bool|null
     */
    public $boolOpt;

    /**
     * @TarsProperty(order=3, required=false, type="int")
     *
     * @var int|null
     */
    public $intOpt;

    /**
     * @TarsProperty(order=4, required=true, type="string")
     *
     * @var string|null
     */
    public $stringRequired;

    /**
     * @TarsProperty(order=5, required=false, type="string")
     *
     * @var string|null
     */
    public $stringOpt;
}
