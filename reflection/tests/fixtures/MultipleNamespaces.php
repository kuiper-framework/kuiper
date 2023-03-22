<?php

declare(strict_types=1);
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
    use NamespaceA\ClassA;
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
