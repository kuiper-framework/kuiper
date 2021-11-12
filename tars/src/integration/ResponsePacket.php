<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 1.0
 */

namespace kuiper\tars\integration;

use kuiper\tars\annotation\TarsProperty;

final class ResponsePacket
{
    /**
     * @TarsProperty(order=1, required=true, type="short")
     *
     * @var int|null
     */
    public $iVersion;

    /**
     * @TarsProperty(order=2, required=true, type="byte")
     *
     * @var int|null
     */
    public $cPacketType = 0;

    /**
     * @TarsProperty(order=3, required=true, type="int")
     *
     * @var int|null
     */
    public $iRequestId;

    /**
     * @TarsProperty(order=4, required=true, type="int")
     *
     * @var int|null
     */
    public $iMessageType = 0;

    /**
     * @TarsProperty(order=5, required=true, type="int")
     *
     * @var int|null
     */
    public $iRet = 0;

    /**
     * @TarsProperty(order=6, required=true, type="vector<byte>")
     *
     * @var string|null
     */
    public $sBuffer;

    /**
     * @TarsProperty(order=7, required=true, type="map<string,string>")
     *
     * @var string[]|null
     */
    public $status;

    /**
     * @TarsProperty(order=8, required=false, type="string")
     *
     * @var string|null
     */
    public $sResultDesc;

    /**
     * @TarsProperty(order=9, required=false, type="map<string,string>")
     *
     * @var string[]|null
     */
    public $context;
}
