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

use Exception;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;

class JsonRpcResponseFactory extends SimpleJsonRpcResponseFactory
{
    public function __construct(
        private readonly RpcResponseNormalizer $normalizer,
        private readonly ExceptionNormalizer $exceptionNormalizer
    ) {
    }

    protected function handleError(JsonRpcRequestInterface $request, int $code, string $message, $data): RpcResponseInterface
    {
        if (null === $data) {
            return parent::handleError($request, $code, $message, $data);
        }
        try {
            $exception = $this->exceptionNormalizer->denormalize($data, '');
        } catch (Exception $e) {
            return parent::handleError($request, $code, $message, $data);
        }
        throw $exception;
    }

    protected function buildResult(RpcMethodInterface $method, $result, array $context): array
    {
        if (array_key_exists(JsonRpcProtocol::EXTENDED, $context)) {
            return $this->normalizer->normalize($method, $result);
        }

        return $this->normalizer->normalize($method, [$result]);
    }
}
