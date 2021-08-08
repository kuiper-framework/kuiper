<?php

declare(strict_types=1);

namespace kuiper\swoole;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testEnv()
    {
        Dotenv::createImmutable(__DIR__.'/fixtures/env', ['.env', '.env.local', '.env.dev', '.env.dev.local'], false)
            ->safeLoad();
        print_r($_ENV);
    }
}
