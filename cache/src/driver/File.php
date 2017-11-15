<?php

namespace kuiper\cache\driver;

class File extends AbstractDriver implements DriverInterface
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var array
     */
    private $locks;

    /**
     * @var string
     */
    protected $separator = '/';

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetch($key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function batchFetch(array $keys)
    {
        $values = [];
        foreach ($keys as $key) {
            $values[] = $this->fetch($key);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    protected function store($key, $data, $expiration)
    {
        $file = $this->getCacheFile($key);
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new \RuntimeException("Cannot create directory '$dir'");
        }

        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function del(array $path)
    {
        $last = end($path);
        if ($last === null) {
            array_pop($path);
        }
        $file = $this->getCacheFile($this->makeKey($path));
        if ($last === null) {
            $this->delTree(dirname($file));
        }
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $dir = dirname($this->getCacheFile(''));
        $this->delTree($dir, !empty($this->prefix));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function lock(array $path, $ttl)
    {
        $lockName = $this->getLockName($path);
        // Silence error reporting
        set_error_handler(function () {
        });
        $file = sprintf('%s/%s.lock', $this->cacheDir, $lockName);

        if (!$fh = fopen($file, 'r')) {
            if ($fh = fopen($file, 'x')) {
                chmod($file, 0444);
            } elseif (!$fh = fopen($file, 'r')) {
                usleep(100); // Give some time for chmod() to complete
                $fh = fopen($file, 'r');
            }
        }
        restore_error_handler();

        if (!$fh) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        // On Windows, even if PHP doc says the contrary, LOCK_NB works, see
        // https://bugs.php.net/54129
        if (!flock($fh, LOCK_EX | LOCK_NB)) {
            fclose($fh);
            $fh = null;

            return false;
        }
        $this->locks[$lockName] = $fh;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(array $path)
    {
        $lockName = $this->getLockName($path);
        if (isset($this->locks[$lockName])) {
            $fh = $this->locks[$lockName];
            flock($fh, LOCK_UN | LOCK_NB);
            fclose($fh);
            unset($this->locks[$lockName]);
        }
    }

    protected function makeKey(array $path)
    {
        return implode($this->separator, array_map([$this, 'sanitizeKey'], $path));
    }

    protected function getLockName(array $path)
    {
        return md5('locks/'.$this->prefix.implode('/', $path));
    }

    /**
     * A simple recursive delTree method.
     *
     * @param string $dir
     * @param bool   $delTop
     */
    protected function delTree($dir, $delTop = false)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        if ($delTop) {
            rmdir($dir);
        }
    }

    protected function sanitizeKey($name)
    {
        return preg_replace('#^-_a-zA-Z0-9#', '', $name);
    }

    protected function getCacheFile($key)
    {
        return $this->cacheDir
            .(empty($this->prefix) ? '' : $this->separator.$this->prefix)
            .$this->separator.$key.'.cache';
    }
}
