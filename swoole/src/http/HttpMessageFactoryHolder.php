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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpMessageFactoryHolder
{
    /**
     * HttpMessageFactoryHolder constructor.
     *
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param ResponseFactoryInterface      $responseFactory
     * @param StreamFactoryInterface        $streamFactory
     * @param UriFactoryInterface           $uriFactory
     * @param UploadedFileFactoryInterface  $uploadFileFactory
     */
    public function __construct(
        private readonly ServerRequestFactoryInterface $serverRequestFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly UploadedFileFactoryInterface $uploadFileFactory)
    {
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
