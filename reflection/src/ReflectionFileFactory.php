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

namespace kuiper\reflection;

class ReflectionFileFactory implements ReflectionFileFactoryInterface
{
    private static ?ReflectionFileFactory $INSTANCE;

    /**
     * @var ReflectionFile[]
     */
    private array $files = [];

    public static function getInstance(): ReflectionFileFactoryInterface
    {
        if (!isset(self::$INSTANCE)) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $file): ReflectionFileInterface
    {
        $filePath = realpath($file);
        if (false === $filePath) {
            throw new \InvalidArgumentException("File '$file' does not exist");
        }

        return $this->files[$filePath] ?? ($this->files[$filePath] = new ReflectionFile($filePath));
    }

    public function clearCache(string $filePath = null): bool
    {
        if (isset($filePath)) {
            $file = realpath($filePath);
            if (false === $file) {
                return false;
            }
            unset($this->files[$file]);

            return true;
        }

        $this->files = [];

        return true;
    }
}
