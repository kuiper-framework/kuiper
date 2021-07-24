<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpMessageFactoryHolder
{
    /**
     * @var ServerRequestFactoryInterface
     */
    private $serverRequestFactory;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var UploadedFileFactoryInterface
     */
    private $uploadFileFactory;

    /**
     * HttpMessageFactoryHolder constructor.
     *
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param ResponseFactoryInterface      $responseFactory
     * @param StreamFactoryInterface        $streamFactory
     * @param UriFactoryInterface           $uriFactory
     * @param UploadedFileFactoryInterface  $uploadFileFactory
     */
    public function __construct(ServerRequestFactoryInterface $serverRequestFactory, ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, UriFactoryInterface $uriFactory, UploadedFileFactoryInterface $uploadFileFactory)
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
        $this->uploadFileFactory = $uploadFileFactory;
    }

    /**
     * @return ServerRequestFactoryInterface
     */
    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->serverRequestFactory;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @return UriFactoryInterface
     */
    public function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }

    /**
     * @return UploadedFileFactoryInterface
     */
    public function getUploadFileFactory(): UploadedFileFactoryInterface
    {
        return $this->uploadFileFactory;
    }
}
