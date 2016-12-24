<?php
namespace kuiper\helper;

use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;

class DataDumper
{
    public static $FORMATS = [
        'json' => 'json',
        'yaml' => 'yaml',
        'yml' => 'yaml',
        'php' => 'php'
    ];

    /**
     * @param mixed $data data to serialize
     * @param bool $pretty
     * @return string
     */
    public static function json($data, $pretty = true)
    {
        if ($pretty) {
            $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            return json_encode($data, $flags) . "\n";
        } else {
            return json_encode($data);
        }
    }

    /**
     * @param mixed $data data to serialize
     * @param bool $pretty
     * @return string
     */
    public static function yaml($data, $pretty = true)
    {
        return Yaml::dump($data, $pretty ? 4 : 2, 2);
    }

    /**
     * @param mixed $data data to serialize
     * @return string
     */
    public static function php($data)
    {
        return var_export($data, true);
    }

    /**
     * serialize to specific format
     *
     * @param mixed $data
     * @param string $format
     * @return string
     */
    public static function dump($data, $format = 'yaml', $pretty = true)
    {
        return self::$format($data, $pretty);
    }

    /**
     * @param string $content
     * @param string $format
     */
    public static function load($content, $format)
    {
        if ($format === 'json') {
            return json_decode($content, true);
        } elseif ($format === 'yaml') {
            return Yaml::parse($content);
        } elseif ($format === 'php') {
            return eval('return ' . $content . ';');
        } else {
            throw new InvalidArgumentException("Invalid format '{$format}'");
        }
    }

    /**
     * Gets data format from file name
     * 
     * @param string $file
     *
     * @return string
     */
    public static function guessFormat($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (!isset(self::$FORMATS[$ext])) {
            throw new InvalidArgumentException("Cannot guess format from file '{$file}'");
        }
        return $format = self::$FORMATS[$ext];
    }

    /**
     * load serialized data
     *
     * @param string $file
     * @param string $format 文件格式，如果未指定，使用文件后缀判断格式
     * @return array
     */
    public static function loadFile($file, $format = null)
    {
        if (!isset($format)) {
            $format = self::guessFormat($file);
        }
        if ($format === 'json') {
            return json_decode(file_get_contents($file), true);
        } elseif ($format === 'yaml') {
            return Yaml::parse(file_get_contents($file));
        } elseif ($format === 'php') {
            return require($file);
        } else {
            throw new InvalidArgumentException("Invalid format '{$format}'");
        }
    }

    /**
     * dump serialized data to file
     *
     * @param string $file
     * @param mixed $data
     * @param string $format 文件格式，如果未指定，使用文件后缀判断格式
     */
    public static function dumpFile($file, $data, $format = null, $pretty = true)
    {
        if (!isset($format)) {
            $format = self::guessFormat($file);
        }
        return file_put_contents($file, self::dump($data, $format, $pretty));
    }
}
