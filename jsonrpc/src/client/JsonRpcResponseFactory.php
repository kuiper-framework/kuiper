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

namespace kuiper\jsonrpc\client;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\exception\ServerException;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;

class JsonRpcResponseFactory extends SimpleJsonRpcResponseFactory
{
    public function __construct(
        private readonly RpcResponseNormalizer $normalizer,
        private readonly ExceptionNormalizer $exceptionNormalizer)
    {
    }

    protected function handleError(JsonRpcRequestInterface $request, int $code, string $message, $data): RpcResponseInterface
    {
        if (null === $data) {
            return parent::handleError($request, $code, $message, $data);
        }
        $exception = $this->exceptionNormalizer->denormalize($data, '');
        throw new ServerException($exception->getMessage(), $exception->getCode(), $exception);
    }

    protected function buildResult(RpcMethodInterface $method, $result): array
    {
        return $this->normalizer->normalize($method, $result);
    }
}
