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

namespace kuiper\swoole\http;

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;
use Swoole\Timer;

class SwooleResponseBridge implements SwooleResponseBridgeInterface
{
    /**
     * @param int $bufferOutputSize swoole default buffer_output_size
     * @param int $tempFileDelay    delay milliseconds to delete template response body file
     */
    public function __construct(private readonly int $bufferOutputSize = 2097152, private readonly int $tempFileDelay = 5000)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function update(ResponseInterface $response, Response $swooleResponse, bool $withContent = true): void
    {
        $swooleResponse->status($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                if (in_array($name, ['content-length', 'transfer-encoding'], true)) {
                    continue;
                }
                $swooleResponse->header($name, $value);
            }
        }
        $body = $response->getBody();
        $contentLength = $body->getSize();

        if ($body instanceof FileStreamInterface) {
            $swooleResponse->sendfile($body->getFileName());

            return;
        }

        if ($contentLength > $this->bufferOutputSize) {
            $tempFile = tempnam(sys_get_temp_dir(), 'swoole-tmp-body');
            file_put_contents($tempFile, (string) $body);
            $swooleResponse->sendfile($tempFile);
            $this->defer(static function () use ($tempFile): void {
                @unlink($tempFile);
            }, $this->tempFileDelay);
        } elseif ($withContent) {
            $swooleResponse->end((string) $body);
        } else {
            $swooleResponse->end();
        }
    }

    private function defer(callable $callback, int $milliseconds): void
    {
        Timer::after($milliseconds, $callback);
    }
}
