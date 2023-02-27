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

namespace kuiper\web\handler;

use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Exception\HttpException;
use Throwable;

class LogErrorRenderer extends PlainTextErrorRenderer
{
    protected function getErrorTitle(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getTitle().', uri='.$exception->getRequest()->getUri();
        }

        return sprintf('%s: %s at %s:%d',
            get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }
}
