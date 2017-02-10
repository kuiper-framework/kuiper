<?php

namespace kuiper\rpc\server;

interface MethodInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return callable
     */
    public function getCallable();
}
