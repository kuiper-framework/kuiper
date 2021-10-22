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

namespace kuiper\tars\client;

use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\exception\ServerException;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\core\TarsMethodInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\exception\ErrorCode;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsInputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class TarsResponseFactory implements RpcResponseFactoryInterface
{
    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        Assert::isInstanceOf($request, TarsRequestInterface::class);
        $packet = ResponsePacket::decode((string) $response->getBody());
        /** @var TarsRequestInterface $request */
        if ($packet->iRequestId > 0 && $packet->iRequestId !== $request->getRequestId()) {
            throw new RequestIdMismatchException();
        }
        if (ErrorCode::SERVER_SUCCESS !== $packet->iRet) {
            if (ErrorCode::INVALID_ARGUMENT === $packet->iRet) {
                throw new \InvalidArgumentException($packet->sResultDesc);
            }
            throw new ServerException($packet->sResultDesc, $packet->iRet);
        }
        /** @var TarsMethodInterface $method */
        $method = $request->getRpcMethod();
        $request = $request->withRpcMethod($method->withResult($this->buildResult($method, $packet)));

        return new TarsResponse($request, $response, $packet);
    }

    /**
     * @throws \kuiper\tars\exception\TarsStreamException
     */
    private function buildResult(TarsMethodInterface $method, ResponsePacket $packet): array
    {
        $returnValues = [];
        if (ErrorCode::SERVER_SUCCESS === $packet->iRet) {
            $is = new TarsInputStream($packet->sBuffer);
            if (TarsConst::VERSION === $packet->iVersion) {
                $ret = $is->readMap(0, true, MapType::byteArrayMap());
                $return = $method->getReturnValue();
                if (isset($ret['']) && !$return->getType()->isVoid()) {
                    $returnValues[] = TarsInputStream::unpack($return->getType(), $ret[''] ?? '');
                } else {
                    $returnValues[] = null;
                }

                foreach ($method->getParameters() as $parameter) {
                    if (!$parameter->isOut()) {
                        continue;
                    }
                    if (isset($ret[$parameter->getName()])) {
                        $returnValues[] = TarsInputStream::unpack($parameter->getType(), $ret[$parameter->getName()]);
                    } else {
                        $returnValues[] = null;
                    }
                }
            } else {
                $return = $method->getReturnValue();
                if ($return->getType()->isVoid()) {
                    $returnValues[] = null;
                } else {
                    $returnValues[] = $is->read(0, true, $return->getType());
                }
                foreach ($method->getParameters() as $parameter) {
                    if ($parameter->isOut()) {
                        $returnValues[] = $is->read($parameter->getOrder(), true, $parameter->getType());
                    }
                }
            }
        }

        return $returnValues;
    }
}
