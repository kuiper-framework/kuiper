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

namespace kuiper\db\converter;

use kuiper\db\metadata\Column;

class JsonConverter implements AttributeConverterInterface
{
    /**
     * @var bool
     */
    private $assoc;
    /**
     * @var int
     */
    private $options;

    public function __construct(bool $assoc = true, ?int $options = null)
    {
        $this->assoc = $assoc;
        $this->options = $options ?? (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseColumn($attribute, Column $column)
    {
        return json_encode($attribute, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToEntityAttribute($dbData, Column $column)
    {
        return json_decode($dbData, $this->assoc);
    }
}
