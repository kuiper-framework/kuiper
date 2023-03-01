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
use kuiper\swoole\logger\RequestLogJsonFormatter;
use kuiper\swoole\logger\LogContext;

class RpcRequestJsonLogFormatter extends RpcRequestLogTextFormatter
{
    public const SERVER = [
        'service', 'method', 'status', 'remote_addr', 'time_local',
        'referer', 'body_bytes_sent', 'body_bytes_recv', 'request_id', 'request_time', 'extra',
    ];

    public const CLIENT = [
        'service', 'method', 'status', 'server_addr', 'time_local',
        'callee_service', 'callee_method',
        'body_bytes_sent', 'body_bytes_recv', 'request_id', 'request_time', 'extra',
    ];

    public function __construct(
        private readonly array $fields = self::SERVER,
        array $extra = ['params', 'pid'],
        int $bodyMaxSize = 4096,
        $dateFormat = 'Y-m-d\TH:i:s.v')
    {
        parent::__construct('', $extra, $bodyMaxSize, $dateFormat);
    }

    /**
     * {@inheritDoc}
     */
    public function format(LogContext $context): array
    {
        $messageContext = $this->prepareMessageContext($context);

        return [RequestLogJsonFormatter::jsonEncode(Arrays::select($messageContext, $this->fields))];
    }
}
