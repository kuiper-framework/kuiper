<?php

namespace kuiper\web;

use Interop\Container\ContainerInterface;
use Mockery;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getContainer()
    {
        return $this->container = Mockery::mock(ContainerInterface::class);
    }
}
