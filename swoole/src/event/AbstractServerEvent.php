<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\swoole\event;

use kuiper\event\StoppableEventTrait;
use kuiper\swoole\server\ServerInterface;
use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractServerEvent implements StoppableEventInterface
{
    use StoppableEventTrait;

    /**
     * @var ServerInterface
     */
    private $server;

    public function getServer(): ServerInterface
    {
        return $this->server;
    }

    public function setServer(ServerInterface $server): void
    {
        $this->server = $server;
    }
}
