<?php

namespace kuiper\reflection;

use InvalidArgumentException;

class ReflectionFileFactory implements ReflectionFileFactoryInterface
{
    /**
     * @var ReflectionFileFactory
     */
    private static $INSTANCE;

    /**
     * @var ReflectionFile[]
     */
    private $files = [];

    /**
     * {@inheritdoc}
     */
    public static function createInstance()
    {
        if (!isset(self::$INSTANCE)) {
            self::$INSTANCE = new self();
        }

        return self::$INSTANCE;
    }

    /**
     * {@inheritdoc}
     */
    public function create($filePath)
    {
        $file = realpath($filePath);
        if (false === $file) {
            throw new InvalidArgumentException("File '$filePath' does not exist");
        }
        if (isset($this->files[$file])) {
            return $this->files[$file];
        } else {
            return $this->files[$file] = new ReflectionFile($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache($filePath = null)
    {
        if (isset($filePath)) {
            $file = realpath($filePath);
            if (false === $file) {
                return false;
            }
            unset($this->files[$file]);

            return true;
        } else {
            $this->files = [];

            return true;
        }
    }
}
