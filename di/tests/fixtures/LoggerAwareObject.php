<?php

namespace kuiper\di\fixtures;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class LoggerAwareObject implements LoggerAwareInterface
{
    use LoggerAwareTrait;
}
