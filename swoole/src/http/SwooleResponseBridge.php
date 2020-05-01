<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use kuiper\swoole\task\DeleteFileTask;
use kuiper\swoole\task\QueueInterface;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

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

    /**
     * @var QueueInterface
     */
    private $taskQueue;

    public function __construct(QueueInterface $queue, int $bufferOutputSize = 2097152, int $tempFileDelay = 5000)
    {
        $this->taskQueue = $queue;
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
        $swooleResponse->header('content-length', (string) $contentLength);

        if ($body instanceof FileStreamInterface) {
            $swooleResponse->sendfile($body->getFileName());

            return;
        }

        if ($contentLength > $this->bufferOutputSize) {
            $file = tempnam(sys_get_temp_dir(), 'swoole-tmp-body');
            file_put_contents($file, (string) $body);
            $swooleResponse->sendfile($file);
            $this->taskQueue->put(new DeleteFileTask($file, $this->tempFileDelay));
        } else {
            if ($contentLength > 0) {
                // $response->end($body) 在 1.9.8 版出现错误
                $swooleResponse->write((string) $body);
            }
            $swooleResponse->end();
        }
    }
}
