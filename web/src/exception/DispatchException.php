<?php
namespace kuiper\web\exception;

use kuiper\web\ServerRequestAwareInterface;
use kuiper\web\ServerRequestAwareTrait;
use kuiper\web\ResponseAwareInterface;
use kuiper\web\ResponseAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class DispatchException extends RuntimeException implements RequestAwareInterface, ResponseAwareInterface
{
    use RequestAwareTrait, ResponseAwareTrait;
}
