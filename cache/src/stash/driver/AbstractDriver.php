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

namespace kuiper\cache\stash\driver;

use kuiper\cache\stash\DriverInterface;

abstract class AbstractDriver implements DriverInterface
{
    protected array $options = [];

    /**
     * Initializes the driver.
     *
     * @param array $options
     *                       An additional array of options to pass through to setOptions()
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    protected function setOptions(array $options = []): void
    {
        $this->options = $options;
    }
}
