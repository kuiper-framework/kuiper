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

use Swoole\WebSocket\Frame;

class MessageEvent extends AbstractServerEvent
{
    /**
     * @var Frame
     */
    private $frame;

    public function getFrame(): Frame
    {
        return $this->frame;
    }

    public function setFrame(Frame $frame): void
    {
        $this->frame = $frame;
    }
}
