<?php

declare(strict_types=1);

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 0.6
 */

namespace kuiper\tars\server\servant;

use kuiper\tars\attribute\TarsProperty;

final class TarsFile
{
    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 0)]
    public readonly string $name;

    /**
     * @var string
     */
    #[TarsProperty(type: "string", order: 1)]
    public readonly string $md5;

    /**
     * @var string|null
     */
    #[TarsProperty(type: "string", order: 2)]
    public readonly ?string $content;

    public function __construct(
        string $name,
        string $md5,
        ?string $content = null
    ) {
          $this->name = $name;
          $this->md5 = $md5;
          $this->content = $content;
    }
}
