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

namespace kuiper\tars\fixtures;


use kuiper\tars\attribute\TarsProperty;

final class RequestWithDefault
{
    public function __construct(

        #[TarsProperty(type: 'int', order: 0)]
        public readonly int $intRequired = 0,

        #[TarsProperty(type: 'bool', order: 1)]
        public readonly bool $boolRequired = false,

        #[TarsProperty(type: 'bool', order: 2)]
        public readonly ?bool $boolOpt = null,

        #[TarsProperty(type: 'int', order: 3)]
        public readonly ?int $intOpt = null,

        #[TarsProperty(type: 'string', order: 4)]
        public readonly string $stringRequired = '',

        #[TarsProperty(type: 'string', order: 5)]
        public readonly ?string $stringOpt = null,

        #[TarsProperty(type: 'long', order: 6)]
        public readonly int $longRequired = 0,

        #[TarsProperty(type: 'vector<string>', order: 7)]
        public readonly ?array $arrayOpt = null,
    )
    {
    }
}
