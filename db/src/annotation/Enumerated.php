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

namespace kuiper\db\annotation;

use Doctrine\Common\Annotations\Annotation\Enum;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Enumerated implements Annotation
{
    public const ORDINAL = 'ORDINAL';
    public const STRING = 'STRING';

    /**
     * @Enum({"ORDINAL", "STRING"})
     *
     * @var string
     */
    public $value = self::ORDINAL;
}
