<?php

declare(strict_types=1);

namespace kuiper\rpc;

use kuiper\helper\Arrays;
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
        'referer', 'callee_servant', 'callee_method',
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

        return [$this->jsonEncode(Arrays::select($messageContext, $this->fields))];
    }

    private function jsonEncode(array $fields): string
    {
        $json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            unset($fields['extra']);
            $json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (false === $json) {
                $json = '';
            }
        }

        return $json;
    }
}
