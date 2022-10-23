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

namespace kuiper\db\attribute;

use kuiper\di\attribute\Service;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Repository extends Service
{
    public function __construct(private readonly string $entityClass)
    {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
