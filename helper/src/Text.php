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

namespace kuiper\helper;

final class Text
{
    /**
     * Converts strings to camel case style.
     *
     * <code>
     *    echo CaseFormat::camelCase('coco_bongo'); // CocoBongo
     *    echo CaseFormat::camelCase('co_co-bon_go', '-'); // Co_coBon_go
     *    echo CaseFormat::camelCase('co_co-bon_go', '_-'); // CoCoBonGo
     * </code>
     *
     * @param string      $str
     * @param string|null $delimiter
     *
     * @return string
     */
    public static function camelCase(string $str, string $delimiter = null): string
    {
        $sep = "\x00";
        $replace = null === $delimiter ? ['_'] : str_split($delimiter);

        return implode('', array_map('ucfirst', explode($sep, str_replace($replace, $sep, $str))));
    }

    /**
     * snake case strings which are camel case.
     *
     * <code>
     *    echo Text::snakeCase('CocoBongo'); // coco_bongo
     *    echo Text::snakeCase('CocoBongo', '-'); // coco-bongo
     * </code>
     *
     * @param string      $str
     * @param string|null $delimiter
     *
     * @return string
     */
    public static function snakeCase(string $str, string $delimiter = null): string
    {
        if (!ctype_lower($str)) {
            $str = preg_replace('/\s+/u', '', ucwords($str));

            $str = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.($delimiter ?? '_'), $str));
        }

        return $str;
    }

    /**
     * Check if a string starts with a given string.
     *
     * <code>
     *    echo Text::startsWith("Hello", "He"); // true
     *    echo Text::startsWith("Hello", "he", false); // false
     *    echo Text::startsWith("Hello", "he"); // true
     * </code>
     *
     * @param string $haystack
     * @param string $needle
     * @param bool   $ignoreCase
     *
     * @return bool
     *
     * @deprecated
     */
    public static function startsWith(string $haystack, string $needle, bool $ignoreCase = true): bool
    {
        if ('' === $needle) {
            return true;
        }

        return $ignoreCase ? 0 === strncasecmp($haystack, $needle, strlen($needle))
            : 0 === strncmp($haystack, $needle, strlen($needle));
    }

    /**
     * Check if a string ends with a given string.
     *
     * <code>
     *    echo Text::endsWith("Hello", "llo"); // true
     *    echo Text::endsWith("Hello", "LLO", false); // false
     *    echo Text::endsWith("Hello", "LLO"); // true
     * </code>
     *
     * @param string $haystack
     * @param string $needle
     * @param bool   $ignoreCase
     *
     * @return bool
     *
     * @deprecated
     */
    public static function endsWith(string $haystack, string $needle, bool $ignoreCase = true): bool
    {
        if ('' === $needle) {
            return true;
        }
        $temp = strlen($haystack) - strlen($needle);
        if ($temp < 0) {
            return false;
        }

        return $ignoreCase ? false !== stripos($haystack, $needle, $temp)
            : false !== strpos($haystack, $needle, $temp);
    }

    /**
     * makes a string lowercase, this function makes use of the mbstring extension if available.
     *
     * <code>
     *    echo Text::lower("HELLO"); // hello
     * </code>
     */
    public static function lower(string $str, string $encoding = 'UTF-8'): string
    {
        /*
         * 'lower' checks for the mbstring extension to make a correct lowercase transformation
         */
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($str, $encoding);
        }

        return strtolower($str);
    }

    /**
     * makes a string uppercase, this function makes use of the mbstring extension if available.
     *
     * <code>
     *    echo Text::upper("hello"); // HELLO
     * </code>
     *
     * @param string $str
     * @param string $encoding
     *
     * @return string
     */
    public static function upper(string $str, string $encoding = 'UTF-8'): string
    {
        /*
         * 'upper' checks for the mbstring extension to make a correct lowercase transformation
         */
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($str, $encoding);
        }

        return strtoupper($str);
    }

    /**
     * Makes a phrase underscored instead of spaced.
     *
     * <code>
     *   echo Text::underscore('look behind'); // 'look_behind'
     *   echo Text::underscore('Awesome Phalcon'); // 'Awesome_Phalcon'
     * </code>
     */
    public static function underscore(string $text): string
    {
        return preg_replace("#\s+#", '_', trim($text));
    }

    /**
     * Makes an underscored or dashed phrase human-readable.
     *
     * <code>
     *   echo Text::humanize('start-a-horse'); // 'start a horse'
     *   echo Text::humanize('five_cats'); // 'five cats'
     * </code>
     */
    public static function humanize(string $text): string
    {
        return preg_replace('#[_-]+#', ' ', trim($text));
    }

    public static function isNotEmpty(?string $text): bool
    {
        return isset($text) && '' !== $text;
    }

    public static function isEmpty(?string $text): bool
    {
        return !isset($text) || '' === $text;
    }

    public static function isInteger(?string $text): bool
    {
        return (bool) preg_match('/^\d+$/', $text);
    }

    public static function truncate(string $value, int $maxLength, string $suffix = '...'): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - strlen($suffix)).$suffix;
    }
}
