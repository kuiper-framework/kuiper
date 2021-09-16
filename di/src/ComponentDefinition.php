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

namespace kuiper\di;

use DI\Definition\Definition;
use kuiper\di\annotation\ComponentInterface;

class ComponentDefinition implements Definition
{
    use DelegateDefinitionTrait;

    /**
     * @var ComponentInterface
     */
    private $component;

    /**
     * ComponentDefintion constructor.
     */
    public function __construct(Definition $definition, ComponentInterface $component)
    {
        $this->definition = $definition;
        $this->component = $component;
    }

    public function getComponent(): ComponentInterface
    {
        return $this->component;
    }
}
