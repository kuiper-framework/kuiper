<?php

namespace kuiper\web\annotation\filter;

abstract class AbstractFilter implements FilterInterface
{
    public $priority = 1024;

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
