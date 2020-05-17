<?php

declare(strict_types=1);

namespace kuiper\web\event;

use kuiper\event\StoppableEventTrait;
use kuiper\web\ResponseAwareInterface;
use kuiper\web\ResponseAwareTrait;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Http\Message\ServerRequestInterface;

class BeforeRequestEvent implements ResponseAwareInterface, StoppableEventInterface
{
    use ResponseAwareTrait;
    use StoppableEventTrait;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * RequestEvent constructor.
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
