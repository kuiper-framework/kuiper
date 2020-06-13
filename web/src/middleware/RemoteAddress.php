<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RemoteAddress implements MiddlewareInterface
{
    private const REMOTE_ADDR = '__ip__';

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute(self::REMOTE_ADDR, self::getAll($request)));
    }

    public static function get(ServerRequestInterface $request): ?string
    {
        $ips = self::getAll($request);

        return $ips[0] ?? null;
    }

    public static function getAll(ServerRequestInterface $request): array
    {
        $ipList = $request->getAttribute(self::REMOTE_ADDR);
        if (is_array($ipList)) {
            return $ipList;
        }

        $server = $request->getServerParams();
        $ipList = [];

        $name = 'X-Forwarded-For';
        $header = $request->getHeaderLine($name);

        if (!empty($header)) {
            foreach (array_map('trim', explode(',', $header)) as $ip) {
                if ((false === array_search($ip, $ipList, true)) && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ipList[] = $ip;
                }
            }
        }

        if (!empty($server['REMOTE_ADDR']) && filter_var($server['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ipList[] = $server['REMOTE_ADDR'];
        }

        return $ipList;
    }
}
