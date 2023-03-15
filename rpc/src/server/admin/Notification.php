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

namespace kuiper\rpc\server\admin;

final class Notification
{
    /**
     * @var string
     */
    public readonly string $createTime;

    /**
     * @var string
     */
    public readonly string $eventId;

    /**
     * @var string
     */
    public readonly string $topic;

    /**
     * @var string
     */
    public readonly string $eventName;

    /**
     * @var string
     */
    public readonly string $payload;

    public function __construct(
        string $createTime,
        string $eventId,
        string $topic,
        string $eventName,
        string $payload
    ) {
        $this->createTime = $createTime;
        $this->eventId = $eventId;
        $this->topic = $topic;
        $this->eventName = $eventName;
        $this->payload = $payload;
    }
}
