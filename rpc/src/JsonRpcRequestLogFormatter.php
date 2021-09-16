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

namespace kuiper\rpc;

use kuiper\helper\Arrays;
use kuiper\swoole\logger\JsonRequestLogFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class JsonRpcRequestLogFormatter extends RpcRequestLogFormatter
{
    public const SERVER = [
        'service', 'method', 'status', 'remote_addr', 'time_local',
        'referer', 'body_bytes_sent', 'request_id', 'request_time', 'extra',
    ];

    public const CLIENT = [
        'service', 'method', 'status', 'server_addr', 'time_local',
        'callee_servant', 'callee_method',
        'body_bytes_sent', 'request_id', 'request_time', 'extra',
    ];

    /**
     * @var string[]
     */
    private $fields;

    public function __construct(
        array $fields = self::SERVER,
        array $extra = ['params', 'pid'],
        int $bodyMaxSize = 4096,
        $dateFormat = 'Y-m-d\TH:i:s.v')
    {
        parent::__construct('', $extra, $bodyMaxSize, $dateFormat);
        $this->fields = $fields;
    }

    public function format(RequestInterface $request, ?ResponseInterface $response, float $responseTime): array
    {
        $messageContext = $this->prepareMessageContext($request, $response, $responseTime);

        return [JsonRequestLogFormatter::jsonEncode(Arrays::select($messageContext, $this->fields))];
    }
}
