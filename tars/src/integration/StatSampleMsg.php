<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


declare(strict_types=1);

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 0.6
 */

namespace kuiper\tars\integration;

use kuiper\tars\attribute\TarsProperty;

final class StatSampleMsg
{
    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 0)]
    public readonly string $unid;

    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 1)]
    public readonly string $masterName;

    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 2)]
    public readonly string $slaveName;

    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 3)]
    public readonly string $interfaceName;

    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 4)]
    public readonly string $masterIp;

    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 5)]
    public readonly string $slaveIp;

    /**
     * @var int
     */
    #[TarsProperty(type: "int", order: 6)]
    public readonly int $depth;

    /**
     * @var int
     */
    #[TarsProperty(type: "int", order: 7)]
    public readonly int $width;

    /**
     * @var int
     */
    #[TarsProperty(type: "int", order: 8)]
    public readonly int $parentWidth;

    public function __construct(
        string $unid,
        string $masterName,
        string $slaveName,
        string $interfaceName,
        string $masterIp,
        string $slaveIp,
        int $depth,
        int $width,
        int $parentWidth
    ) {
          $this->unid = $unid;
          $this->masterName = $masterName;
          $this->slaveName = $slaveName;
          $this->interfaceName = $interfaceName;
          $this->masterIp = $masterIp;
          $this->slaveIp = $slaveIp;
          $this->depth = $depth;
          $this->width = $width;
          $this->parentWidth = $parentWidth;
    }
}
