<?php

namespace kuiper\di\resolver;

interface ResolvableInterface
{
    /**
     * @return ResolverInterface
     */
    public function getResolver(ResolverInterface $parent);
}
