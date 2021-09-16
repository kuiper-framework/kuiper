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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class JsonRequestLogFormatter extends LineRequestLogFormatter
{
    public const MAIN = ['remote_addr', 'remote_user', 'time_local', 'request', 'status', 'body_bytes_sent', 'http_referer',
        'http_user_agent', 'http_x_forwarded_for', 'request_time', ];

    /**
     * @var string[]
     */
    private $fields;

    public function __construct(
        array $fields = self::MAIN,
        array $extra = ['query', 'body'],
        int $bodyMaxSize = 4096,
        $dateFormat = 'Y-m-d\TH:i:s.v')
    {
        parent::__construct('', $extra, $bodyMaxSize, $dateFormat);
        $this->fields = $fields;
    }

    public function format(RequestInterface $request, ?ResponseInterface $response, float $responseTime): array
    {
        $messageContext = $this->prepareMessageContext($request, $response, $responseTime);

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
