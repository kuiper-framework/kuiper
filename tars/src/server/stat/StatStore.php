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

namespace kuiper\tars\server\stat;

use Iterator;

interface StatStore
{
    public function save(StatEntry $entry): void;

    public function delete(StatEntry $entry): void;

    /**
     * @param int $maxIndex
     *
     * @return Iterator<StatEntry>
     */
    public function getEntries(int $maxIndex): Iterator;
}
