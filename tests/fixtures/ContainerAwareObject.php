<?php

namespace kuiper\di\fixtures;

use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;

class ContainerAwareObject implements ContainerAwareInterface
{
    use ContainerAwareTrait;
}
