<?php

namespace kuiper\di\resolver;

interface ResolvableInterface
{
    /**
     * @param ResolverInterface $parent
     *
     * @return ResolverInterface
     */
    public function getResolver(ResolverInterface $parent);
}
