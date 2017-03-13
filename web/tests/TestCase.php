<?php

namespace kuiper\web;

use Mockery;
use Psr\Container\ContainerInterface;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getContainer()
    {
        return $this->container = Mockery::mock(ContainerInterface::class);
    }
}
