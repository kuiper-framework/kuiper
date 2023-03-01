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

namespace kuiper\swoole\logger;

use kuiper\helper\Arrays;

class RequestLogJsonFormatter extends RequestLogTextFormatter
{
    public const MAIN = ['remote_addr', 'remote_user', 'time_local', 'request', 'status', 'body_bytes_sent', 'http_referer',
        'http_user_agent', 'http_x_forwarded_for', 'request_time', 'extra', ];

    public function __construct(
        private readonly array $fields = self::MAIN,
        array $extra = ['query', 'body', 'pid'],
        int $bodyMaxSize = 4096,
        string|callable $dateFormat = 'Y-m-d\TH:i:s.v')
    {
        parent::__construct('', $extra, $bodyMaxSize, $dateFormat);
    }

    /**
     * {@inheritDoc}
     */
    public function format(LogContext $context): array
    {
        $messageContext = $this->prepareMessageContext($context);

        return [self::jsonEncode(Arrays::select($messageContext, $this->fields))];
    }

    public static function jsonEncode(array $fields): string
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
