<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use kuiper\swoole\constants\HttpHeaderName;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;
use Swoole\Timer;

class SwooleResponseBridge implements SwooleResponseBridgeInterface
{
    /**
     * swoole default buffer_output_size.
     *
     * @var int
     */
    private $bufferOutputSize;

    /**
     * Delay milliseconds to delete template response body file.
     *
     * @var int
     */
    private $tempFileDelay;

    public function __construct(int $bufferOutputSize = 2097152, int $tempFileDelay = 5000)
    {
        $this->bufferOutputSize = $bufferOutputSize;
        $this->tempFileDelay = $tempFileDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function update(ResponseInterface $response, Response $swooleResponse): void
    {
        $swooleResponse->status($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $swooleResponse->header($name, $value);
            }
        }
        $body = $response->getBody();
        $contentLength = $body->getSize();
        $swooleResponse->header(HttpHeaderName::CONTENT_LENGTH, (string) $contentLength);

        if ($body instanceof FileStreamInterface) {
            $swooleResponse->sendfile($body->getFileName());

            return;
        }

        if ($contentLength > $this->bufferOutputSize) {
            $tempFile = tempnam(sys_get_temp_dir(), 'swoole-tmp-body');
            file_put_contents($tempFile, (string) $body);
            $swooleResponse->sendfile($tempFile);
            $this->defer(static function () use ($tempFile) {
                @unlink($tempFile);
            }, $this->tempFileDelay);
        } else {
            if ($contentLength > 0) {
                // $response->end($body) 在 1.9.8 版出现错误
                $swooleResponse->write((string) $body);
            }
            $swooleResponse->end();
        }
    }

    private function defer($callback, int $milliseconds): void
    {
        Timer::after($milliseconds, $callback);
    }
}
