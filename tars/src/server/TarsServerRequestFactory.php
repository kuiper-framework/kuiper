<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\InvokingMethod;
use kuiper\rpc\server\ServerRequestFactoryInterface;
use kuiper\tars\core\MethodMetadata;
use kuiper\tars\core\MethodMetadataFactoryInterface;
use kuiper\tars\exception\ErrorCode;
use kuiper\tars\exception\TarsRequestException;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsInputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class TarsServerRequestFactory implements ServerRequestFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var array
     */
    private $servants;

    /**
     * @var MethodMetadataFactoryInterface
     */
    private $methodMetadataFactory;

    /**
     * TarsServerRequestFactory constructor.
     */
    public function __construct(ServerProperties $serverProperties, MethodMetadataFactoryInterface $methodMetadataFactory, array $servants)
    {
        foreach ($serverProperties->getAdapters() as $adapter) {
            $servantName = $adapter->getServant();
            $this->servants[$adapter->getEndpoint()->getPort()][$servantName] = $servants[$servantName] ?? null;
        }
        $this->methodMetadataFactory = $methodMetadataFactory;
    }

    public function createRequest(RequestInterface $request): \kuiper\rpc\RequestInterface
    {
        $packet = RequestPacket::decode((string) $request->getBody());
        $servant = $this->servants[$request->getUri()->getPort()][$packet->sServantName] ?? null;
        if (!isset($servant)) {
            $this->logger->warning(static::TAG.'cannot find adapter match servant, check config file');
            throw new TarsRequestException($packet, 'Unknown servant '.$packet->sServantName, ErrorCode::SERVER_NO_SERVANT_ERR);
        }
        $methodMetadata = $this->methodMetadataFactory->create($servant, $packet->sFuncName);
        $invokingMethod = new InvokingMethod($servant, $packet->sFuncName, $this->resolveParams($methodMetadata, $packet));

        return new TarsServerRpcRequest($request, $invokingMethod, $packet, $methodMetadata);
    }

    private function resolveParams(MethodMetadata $methodMetadata, RequestPacket $packet): array
    {
        $is = new TarsInputStream($packet->sBuffer);
        $parameters = [];
        if (TarsConst::VERSION === $packet->iVersion) {
            $params = $is->readMap(0, true, MapType::byteArrayMap());
            foreach ($methodMetadata->getParameters() as $i => $parameter) {
                if (isset($params[$parameter->getName()])) {
                    $is = new TarsInputStream($params[$parameter->getName()]);
                    $parameters[] = $is->read(0, true, $parameter->getType());
                } else {
                    $parameters[] = null;
                }
            }
        } else {
            foreach ($methodMetadata->getParameters() as $i => $parameter) {
                if ($parameter->isOut()) {
                    $parameters[] = null;
                } else {
                    $parameters[] = $is->read(0, true, $parameter->getType());
                }
            }
        }

        return $parameters;
    }
}
