<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface RequestInterface extends \Psr\Http\Message\RequestInterface
{
    public function getInvokingMethod(): InvokingMethod;
}
