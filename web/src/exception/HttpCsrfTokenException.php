<?php

declare(strict_types=1);

namespace kuiper\web\exception;

use Slim\Exception\HttpBadRequestException;

class HttpCsrfTokenException extends HttpBadRequestException
{
}
