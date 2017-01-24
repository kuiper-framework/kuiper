<?php

namespace kuiper\di\source;

use kuiper\di\definition\ObjectDefinition;
use kuiper\di\DefinitionEntry;

/**
 * @author Ye Wenbin<yewenbin@phoenixos.com>
 */
class ObjectSource implements SourceInterface
{
    const CLASS_NAME_REGEX = '/^\\\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return preg_match(self::CLASS_NAME_REGEX, $name) && class_exists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return new DefinitionEntry($name, new ObjectDefinition($name));
        }
    }
}
