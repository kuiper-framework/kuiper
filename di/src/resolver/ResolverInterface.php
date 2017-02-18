<?php

namespace kuiper\di\resolver;

use kuiper\di\ContainerInterface;
use kuiper\di\DefinitionEntry;

interface ResolverInterface
{
    /**
     * @param ContainerInterface $container
     * @param DefinitionEntry    $entry
     * @param array              $parameters
     *
     * @return mixed
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = []);
}
