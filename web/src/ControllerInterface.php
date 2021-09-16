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

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface
{
    /**
     * Initialize controller.
     * if return ResponseInterface, stop execute action and return the response.
     *
     * @return ResponseInterface|void|null
     */
    public function initialize();

    /**
     * Sets request.
     *
     * @return static
     */
    public function withRequest(ServerRequestInterface $request);

    /**
     * Sets response.
     *
     * @return static
     */
    public function withResponse(ResponseInterface $response);

    /**
     * Gets the response.
     */
    public function getResponse(): ResponseInterface;
}
