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

namespace kuiper\tars\client\middleware;

use kuiper\rpc\exception\ServerException;
use kuiper\rpc\exception\TimedOutException;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\client\TarsResponse;
use kuiper\tars\server\stat\StatInterface;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class RequestStat implements MiddlewareInterface
{
    /**
     * @var StatInterface
     */
    private $stat;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * Stat constructor.
     *
     * @param StatInterface            $stat
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(StatInterface $stat, ResponseFactoryInterface $responseFactory)
    {
        $this->stat = $stat;
        $this->response = $responseFactory->createResponse();
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $time = microtime(true);
        try {
            /** @var TarsResponse $response */
            $response = $handler->handle($request);
            $responseTime = (int) (1000 * (microtime(true) - $time));
            $this->stat->success($response, $responseTime);

            return $response;
        } catch (ServerException|TimedOutException $e) {
            $responseTime = (int) (1000 * (microtime(true) - $time));
            $packet = new ResponsePacket();
            $response = new TarsResponse($request, $this->response, $packet);
            if ($e instanceof ServerException) {
                $packet->iRet = $e->getCode();
                $packet->sResultDesc = $e->getMessage();
                $this->stat->fail($response, $responseTime);
            } else {
                $this->stat->timedOut($response, $responseTime);
            }
            throw $e;
        }
    }
}
