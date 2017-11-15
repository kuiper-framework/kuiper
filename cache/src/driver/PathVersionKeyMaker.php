<?php

namespace kuiper\cache\driver;

trait PathVersionKeyMaker
{
    /**
     * @var string
     */
    protected $separator = '::';

    protected function makeKeyAndPathKey(array $path, &$pathKey = null)
    {
        $first = array_shift($path);
        $key = sprintf('_cache%s%s', $this->separator, $first);
        while ($path) {
            $name = array_shift($path);
            $pathKey = $this->prefix.$this->transformKey(sprintf('_path%s%s', $this->separator, $key));
            if (isset($name)) {
                $key .= sprintf('_%d%s%s', $this->getCacheVersion($pathKey), $this->separator, $name);
            }
        }

        return $this->prefix.$this->transformKey($key);
    }

    protected function transformKey($key)
    {
        return md5($key);
    }

    /**
     * @param array $path
     *
     * @return string
     */
    protected function makeKey(array $path)
    {
        return $this->makeKeyAndPathKey($path);
    }

    /**
     * {@inheritdoc}
     */
    public function del(array $path)
    {
        $pathKey = null;
        $key = $this->makeKeyAndPathKey($path, $pathKey);
         // error_log("del $realKey origin=". json_encode($path));
        $last = end($path);
        $this->delete($key);
        if ($last === null) {
            $this->incr($pathKey);
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return int
     */
    protected function incr($key)
    {
        $value = ($this->fetch($key) ?: 0) + 1;
        $this->store($key, $value);

        return $value;
    }

    protected function delete(/* @noinspection PhpUnusedParameterInspection */$key)
    {
        throw new \BadMethodCallException('delete method should override');
    }

    /**
     * @param string $key
     *
     * @return int
     */
    protected function getCacheVersion($key)
    {
        return $this->fetch($key) ?: 0;
    }
}
