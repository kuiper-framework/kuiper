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

namespace kuiper\http\client;

use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\exception\BadResponseException;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\RpcResponseInterface;
use Psr\Http\Message\ResponseInterface;

class HttpJsonResponseFactory implements RpcResponseFactoryInterface
{
    /**
     * DefaultResponseParser constructor.
     */
    public function __construct(
        private readonly RpcResponseNormalizer $normalizer,
        private readonly ?string $node = null)
    {
    }

    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        try {
            $method = $request->getRpcMethod()->withResult($this->buildResult($request->getRpcMethod(), $response));
        } catch (\InvalidArgumentException $e) {
            throw new BadResponseException($request, $response, $e);
        }

        return new RpcResponse($request->withRpcMethod($method), $response);
    }

    protected function buildResult(RpcMethodInterface $method, ResponseInterface $response): array
    {
        $contentType = $response->getHeaderLine('content-type');
        if (false !== stripos($contentType, 'application/json')) {
            $data = json_decode((string) $response->getBody(), true);

            return $this->parse($method, $data);
        }

        return [null];
    }

    /**
     * @param RpcMethodInterface $method
     * @param array              $data
     *
     * @return null[]
     */
    protected function parse(RpcMethodInterface $method, array $data): array
    {
        if (null !== $this->node && isset($data[$this->node])) {
            $data = $data[$this->node];
        }

        return $this->normalizer->normalize($method, [$data]);
    }

    /**
     * @return string|null
     */
    public function getNode(): ?string
    {
        return $this->node;
    }

    /**
     * @return RpcResponseNormalizer
     */
    public function getNormalizer(): RpcResponseNormalizer
    {
        return $this->normalizer;
    }
}
