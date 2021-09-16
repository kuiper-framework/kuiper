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

namespace kuiper\jsonrpc\exception;

class JsonRpcRequestException extends \InvalidArgumentException
{
    /**
     * @var int|null
     */
    private $requestId;

    /**
     * JsonRpcRequestException constructor.
     */
    public function __construct(?int $requestId, string $message, int $code)
    {
        parent::__construct($message, $code);
        $this->requestId = $requestId;
    }

    public function getRequestId(): ?int
    {
        return $this->requestId;
    }
}
