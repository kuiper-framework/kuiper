<?php

namespace kuiper\di\source;

use kuiper\di\definition\ValueDefinition;
use kuiper\di\DefinitionEntry;

class EnvSource implements SourceInterface
{
    /**
     * Search the different places for environment variables and return first value found.
     *
     * @param string $name
     *
     * @return string
     */
    public static function findEnvironmentVariable($name)
    {
        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        } elseif (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        } else {
            return getenv($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return self::findEnvironmentVariable($name) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        $value = self::findEnvironmentVariable($name);
        if ($value !== false) {
            return new DefinitionEntry($name, new ValueDefinition($value));
        }
    }
}
