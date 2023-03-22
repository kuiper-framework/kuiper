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

use DI\DependencyException;

use function DI\factory;

use PHPUnit\Framework\TestCase;
use Swoole\Coroutine;

class CoroutineTest extends TestCase
{
    public function testNoLock(): void
    {
        $errors = $this->check(new \DI\ContainerBuilder());
        $this->assertCount(1, $errors);
    }

    public function testLock(): void
    {
        $errors = $this->check(new ContainerBuilder());
        $this->assertEmpty($errors);
    }

    public function check($containerBuilder): array
    {
        $errors = [];
        Coroutine\run(function () use ($containerBuilder, &$errors) {
            $container = null;
            $containerBuilder->addDefinitions([
                'bar' => 1,
                'foo' => factory(function () use (&$container) {
                    usleep(5000);

                    return $container->get('bar') + 1;
                }),
            ]);
            $container = $containerBuilder->build();
            \kuiper\swoole\coroutine\Coroutine::enable();
            $call = static function () use ($container, &$errors) {
                try {
                    echo sprintf("cid=%d, foo=%d\n", Coroutine::getCid(), $container->get('foo'));
                } catch (DependencyException $e) {
                    $errors[] = $e;
                }
            };
            Coroutine::create($call);
            Coroutine::create($call);
        });

        return $errors;
    }
}
