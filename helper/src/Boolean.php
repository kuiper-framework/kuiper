<?php

declare(strict_types=1);

namespace kuiper\helper;

class Boolean extends Enum
{
    public const TRUE = true;
    public const FALSE = false;

    /**
     * @var array
     */
    protected static $PROPERTIES = [
        'description' => [
            self::FALSE => 'False',
            self::TRUE => 'True',
        ],
    ];

    /**
     * @param string|bool|mixed $value
     *
     * @return bool|null "true", "1", 1, true = TRUE
     *                   "false", "0", 0, false = FALSE
     *                   other value = null
     */
    public static function valueOf($value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $name = strtoupper($value);
        if (self::hasName($name)) {
            return parent::valueOf($name);
        }

        if (in_array($name, ['0', '1'], true)) {
            return (bool) $value;
        }

        return null;
    }
}
