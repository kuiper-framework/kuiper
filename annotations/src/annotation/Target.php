<?php

namespace kuiper\annotations\annotation;

use InvalidArgumentException;

/**
 * Annotation that can be used to signal to the parser
 * to check the annotation target during the parsing process.
 *
 * @Annotation
 */
final class Target
{
    const TARGET_CLASS = 1;
    const TARGET_METHOD = 2;
    const TARGET_PROPERTY = 4;
    const TARGET_ANNOTATION = 8;
    const TARGET_ALL = 15;

    /**
     * @var array
     */
    private static $TARGETS = [
        'ALL' => self::TARGET_ALL,
        'CLASS' => self::TARGET_CLASS,
        'METHOD' => self::TARGET_METHOD,
        'PROPERTY' => self::TARGET_PROPERTY,
        'ANNOTATION' => self::TARGET_ANNOTATION,
    ];

    /**
     * @var array
     */
    public $value;

    /**
     * Targets as bitmask.
     *
     * @var int
     */
    public $targets;

    /**
     * Annotation constructor.
     *
     * @param array $values
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $values)
    {
        if (!isset($values['value'])) {
            $values['value'] = [];
        }
        $value = $values['value'];
        if (is_string($value)) {
            $value = [$value];
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf('@Target expects either a string value, or an array of strings, "%s" given.',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $bitmask = 0;
        foreach ($value as $literal) {
            if (!isset(self::$TARGETS[$literal])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid Target "%s". Available targets: [%s]',
                        $literal,
                        implode(', ', array_keys(self::$TARGETS))
                    )
                );
            }
            $bitmask |= self::$TARGETS[$literal];
        }

        $this->targets = $bitmask;
        $this->value = $value;
    }

    public static function describe($value)
    {
        $literals = [];
        foreach (self::$TARGETS as $literal => $bit) {
            if ($bit === self::TARGET_ALL) {
                continue;
            }
            if (($value & $bit) !== 0) {
                $literals[] = $literal;
            }
        }

        return implode(',', $literals);
    }
}
