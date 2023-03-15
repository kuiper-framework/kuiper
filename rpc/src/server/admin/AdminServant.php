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

interface AdminServant
{
    /**
     * For healthy check.
     */
    public function ping(): string;

    /**
     * Get server stat.
     */
    public function stats(): Stat;

    /**
     * receive notification.
     */
    public function notify(Notification $notification): void;
}
