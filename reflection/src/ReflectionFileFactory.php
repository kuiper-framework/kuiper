<?php

declare(strict_types=1);

namespace kuiper\reflection;

class ReflectionFileFactory implements ReflectionFileFactoryInterface
{
    /**
     * @var ReflectionFileFactory|null
     */
    private static $INSTANCE;

    /**
     * @var ReflectionFile[]
     */
    private $files = [];

    public static function getInstance(): ReflectionFileFactoryInterface
    {
        if (null === self::$INSTANCE) {
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
        if (isset($this->files[$filePath])) {
            return $this->files[$filePath];
        }

        return $this->files[$filePath] = new ReflectionFile($filePath);
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
