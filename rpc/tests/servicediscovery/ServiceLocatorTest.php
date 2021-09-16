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

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\ServiceLocator;
use PHPUnit\Framework\TestCase;

class ServiceLocatorTest extends TestCase
{
    public function testFromString()
    {
        $locator = new ServiceLocator('a');
        $parsed = ServiceLocator::fromString((string) $locator);
        $this->assertTrue($parsed->equals($locator));
        $service = ServiceLocator::fromString('a');
        $this->assertTrue($service->equals($locator));
        $service = ServiceLocator::fromString('a:1.0');
        $this->assertTrue($service->equals($locator));
    }
}
