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

namespace kuiper\annotations;

use kuiper\di\annotation\Bean;
use kuiper\swoole\pool\ConnectionProxyGenerator;
use kuiper\swoole\pool\PoolFactoryInterface;

class AnnotationConfiguration
{
    /**
     * @Bean
     */
    public function annotationReader(PoolFactoryInterface $poolFactory): AnnotationReaderInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ConnectionProxyGenerator::create(
            $poolFactory, AnnotationReaderInterface::class, [AnnotationReader::class, 'create']);
    }
}
