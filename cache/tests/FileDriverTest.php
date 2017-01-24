<?php

namespace kuiper\cache;

use kuiper\cache\driver\File;

class FileDriverTest extends BaseDriverTestCase
{
    private $cacheDir;

    protected function tearDown()
    {
        parent::tearDown();
        if (is_dir($this->cacheDir)) {
            $this->delTree($this->cacheDir);
        }
    }

    protected function createCachePool()
    {
        $driver = new File($this->cacheDir = sys_get_temp_dir().'/kuiper-cache');

        return new Pool($driver);
    }

    /**
     * A simple recursive delTree method.
     *
     * @param string $dir
     *
     * @return bool
     */
    protected function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
