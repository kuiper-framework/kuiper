<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\exception\ServerException;
use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\core\MethodMetadata;
use kuiper\tars\core\MethodMetadataFactoryInterface;
use kuiper\tars\exception\ErrorCode;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsInputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class TarsResponseFactory implements RpcResponseFactoryInterface
{
    /**
     * @var MethodMetadataFactoryInterface
     */
    private $methodMetadataFactory;

    /**
     * TarsRpcResponseFactory constructor.
     */
    public function __construct(MethodMetadataFactoryInterface $methodMetadataFactory)
    {
        $this->methodMetadataFactory = $methodMetadataFactory;
    }

    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        Assert::isInstanceOf($request, HasRequestIdInterface::class);
        $packet = ResponsePacket::decode((string)$response->getBody());
        /** @var HasRequestIdInterface|RpcRequestInterface $request */
        if ($packet->iRequestId > 0 && $packet->iRequestId !== $request->getRequestId()) {
            throw new RequestIdMismatchException();
        }
        if (ErrorCode::SERVER_SUCCESS !== $packet->iRet) {
            throw new ServerException($packet->sResultDesc, $packet->iRet);
        }
        $metadata = $this->methodMetadataFactory->create(
            $request->getInvokingMethod()->getTarget(),
            $request->getInvokingMethod()->getMethodName()
        );
        $request->getInvokingMethod()->setResult($this->buildResult($metadata, $packet));

        return new TarsResponse($request, $response, $packet);
    }

    private function buildResult(MethodMetadata $metadata, ResponsePacket $packet): array
    {
        $returnValues = [];
        if (ErrorCode::SERVER_SUCCESS === $packet->iRet) {
            $is = new TarsInputStream($packet->sBuffer);
            if (TarsConst::VERSION === $packet->iVersion) {
                $ret = $is->readMap(0, true, MapType::byteArrayMap());
                $return = $metadata->getReturnValue();
                if (isset($ret['']) && !$return->getType()->isVoid()) {
                    $returnValues[] = TarsInputStream::unpack($return->getType(), $ret[''] ?? '');
                } else {
                    $returnValues[] = null;
                }

                foreach ($metadata->getParameters() as $parameter) {
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
                $return = $metadata->getReturnValue();
                if ($return->getType()->isVoid()) {
                    $returnValues[] = null;
                } else {
                    $returnValues[] = $is->read(0, true, $return->getType());
                }
                foreach ($metadata->getParameters() as $parameter) {
                    if ($parameter->isOut()) {
                        $returnValues[] = $is->read($parameter->getOrder(), true, $parameter->getType());
                    }
                }
            }
        }

        return $returnValues;
    }
}
