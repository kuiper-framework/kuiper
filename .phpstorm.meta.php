<?php

declare(strict_types=1);

/**
 * PhpStorm code completion.
 *
 * Add code completion for PSR-11 Container Interface and more...
 */

namespace PHPSTORM_META;

    use Psr\Container\ContainerInterface as PsrContainerInterface;

    // PSR-11 Container Interface
    override(PsrContainerInterface::get(0),
        map([
            '' => '@',
        ])
    );
