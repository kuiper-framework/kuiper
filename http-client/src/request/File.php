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

namespace kuiper\http\client\request;

class File
{
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $name;

    /**
     * File constructor.
     *
     * @param string      $path
     * @param string|null $name
     */
    public function __construct(string $path, string $name = null)
    {
        $this->path = $path;
        $this->name = $name ?? basename($path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
