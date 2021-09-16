<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(encoding='UTF-8');

namespace NamespaceA
{
    class ClassA
    {
    }

    interface InterfaceA
    {
    }
}

namespace NamespaceB
{
    class ClassB
    {
    }

    interface InterfaceB
    {
    }
}

namespace {
    class ClassC
    {
        public static $classA;
    }

    interface InterfaceC
    {
    }

    ClassC::$classA = NamespaceA\ClassA::class;
}
