<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

use Swoole\Http\Request;

class OpenEvent extends AbstractServerEvent
{
    /**
     * @var Request
     */
    private $request;

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }
}
