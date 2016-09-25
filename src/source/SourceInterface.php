<?php
namespace kuiper\di\source;

interface SourceInterface
{
    /**
     * Gets Definition
     *
     * @param string $name
     * @return \kuiper\di\DefinitionEntry
     */
    public function get($name);

    /**
     * Checks entry existence
     *
     * @param string $name
     * @return bool
     */
    public function has($name);
}
