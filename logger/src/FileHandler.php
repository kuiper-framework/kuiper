<?php

declare(strict_types=1);

namespace kuiper\logger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Utils;

class FileHandler extends AbstractProcessingHandler
{
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var bool
     */
    private $dirCreated;

    public function __construct(string $stream, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->fileName = Utils::canonicalizePath($stream);
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    protected function write(array $record): void
    {
        $url = $this->fileName;
        $this->createDir($url);
        file_put_contents($url, (string) $record['formatted'], FILE_APPEND | LOCK_EX);
    }

    private function getDirFromStream(string $stream): ?string
    {
        $pos = strpos($stream, '://');
        if (false === $pos) {
            return dirname($stream);
        }

        if (0 === strpos($stream, 'file://')) {
            return dirname(substr($stream, 7));
        }

        return null;
    }

    private function createDir(string $url): void
    {
        // Do not try to create dir if it has already been tried.
        if ($this->dirCreated) {
            return;
        }

        $dir = $this->getDirFromStream($url);
        if (null !== $dir && !is_dir($dir)) {
            $status = mkdir($dir, 0777, true);
            if (false === $status && !is_dir($dir)) {
                throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s" and it could not be created', $dir));
            }
        }
        $this->dirCreated = true;
    }
}
