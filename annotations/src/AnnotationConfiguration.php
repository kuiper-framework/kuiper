<?php

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
        return ConnectionProxyGenerator::create($poolFactory, AnnotationReaderInterface::class, [AnnotationReader::class, 'create']);
    }
}
