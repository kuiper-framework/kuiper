<?php

declare(strict_types=1);

namespace kuiper\cache;

use kuiper\swoole\pool\PoolInterface;

class Redis extends \Redis
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * Redis constructor.
     */
    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    public function append($key, $value)
    {
        return $this->pool->take()->append($key, $value);
    }

    public function auth($password)
    {
        return $this->pool->take()->auth($password);
    }

    public function bgSave()
    {
        return $this->pool->take()->bgSave();
    }

    public function bgrewriteaof()
    {
        return $this->pool->take()->bgrewriteaof();
    }

    public function bitcount($key)
    {
        return $this->pool->take()->bitcount($key);
    }

    public function bitop($operation, $ret_key, $key, ...$other_keys)
    {
        return $this->pool->take()->bitop($operation, $ret_key, $key, ...$other_keys);
    }

    public function bitpos($key, $bit, $start = null, $end = null)
    {
        return $this->pool->take()->bitpos($key, $bit, $start, $end);
    }

    public function blPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->pool->take()->blPop($key, $timeout_or_key, ...$extra_args);
    }

    public function brPop($key, $timeout_or_key, ...$extra_args)
    {
        return $this->pool->take()->brPop($key, $timeout_or_key, ...$extra_args);
    }

    public function brpoplpush($src, $dst, $timeout)
    {
        return $this->pool->take()->brpoplpush($src, $dst, $timeout);
    }

    public function bzPopMax($key, $timeout_or_key, ...$extra_args)
    {
        return $this->pool->take()->bzPopMax($key, $timeout_or_key, ...$extra_args);
    }

    public function bzPopMin($key, $timeout_or_key, ...$extra_args)
    {
        return $this->pool->take()->bzPopMin($key, $timeout_or_key, ...$extra_args);
    }

    public function clearLastError()
    {
        return $this->pool->take()->clearLastError();
    }

    public function client($cmd, ...$args)
    {
        return $this->pool->take()->client($cmd, ...$args);
    }

    public function close()
    {
        return $this->pool->take()->close();
    }

    public function command(...$args)
    {
        return $this->pool->take()->command(...$args);
    }

    public function config($cmd, $key, $value = null)
    {
        return $this->pool->take()->config($cmd, $key, $value);
    }

    public function connect($host, $port = null, $timeout = null, $retry_interval = null)
    {
        return $this->pool->take()->connect($host, $port, $timeout, $retry_interval);
    }

    public function dbSize()
    {
        return $this->pool->take()->dbSize();
    }

    public function debug($key)
    {
        return $this->pool->take()->debug($key);
    }

    public function decr($key)
    {
        return $this->pool->take()->decr($key);
    }

    public function decrBy($key, $value)
    {
        return $this->pool->take()->decrBy($key, $value);
    }

    public function del($key, ...$other_keys)
    {
        return $this->pool->take()->del($key, ...$other_keys);
    }

    public function discard()
    {
        return $this->pool->take()->discard();
    }

    public function dump($key)
    {
        return $this->pool->take()->dump($key);
    }

    public function echo($msg)
    {
        return $this->pool->take()->echo($msg);
    }

    public function eval($script, $args = null, $num_keys = null)
    {
        return $this->pool->take()->eval($script, $args, $num_keys);
    }

    public function evalsha($script_sha, $args = null, $num_keys = null)
    {
        return $this->pool->take()->evalsha($script_sha, $args, $num_keys);
    }

    public function exec()
    {
        return $this->pool->take()->exec();
    }

    public function exists($key, ...$other_keys)
    {
        return $this->pool->take()->exists($key, ...$other_keys);
    }

    public function expire($key, $timeout)
    {
        return $this->pool->take()->expire($key, $timeout);
    }

    public function expireAt($key, $timestamp)
    {
        return $this->pool->take()->expireAt($key, $timestamp);
    }

    public function flushAll($async = null)
    {
        return $this->pool->take()->flushAll($async);
    }

    public function flushDB($async = null)
    {
        return $this->pool->take()->flushDB($async);
    }

    public function geoadd($key, $lng, $lat, $member, ...$other_triples)
    {
        return $this->pool->take()->geoadd($key, $lng, $lat, $member, ...$other_triples);
    }

    public function geodist($key, $src, $dst, $unit = null)
    {
        return $this->pool->take()->geodist($key, $src, $dst, $unit);
    }

    public function geohash($key, $member, ...$other_members)
    {
        return $this->pool->take()->geohash($key, $member, ...$other_members);
    }

    public function geopos($key, $member, ...$other_members)
    {
        return $this->pool->take()->geopos($key, $member, ...$other_members);
    }

    public function georadius($key, $lng, $lan, $radius, $unit, array $opts = null)
    {
        return $this->pool->take()->georadius($key, $lng, $lan, $radius, $unit, $opts);
    }

    public function georadius_ro($key, $lng, $lan, $radius, $unit, array $opts = null)
    {
        return $this->pool->take()->georadius_ro($key, $lng, $lan, $radius, $unit, $opts);
    }

    public function georadiusbymember($key, $member, $radius, $unit, array $opts = null)
    {
        return $this->pool->take()->georadiusbymember($key, $member, $radius, $unit, $opts);
    }

    public function georadiusbymember_ro($key, $member, $radius, $unit, array $opts = null)
    {
        return $this->pool->take()->georadiusbymember_ro($key, $member, $radius, $unit, $opts);
    }

    public function get($key)
    {
        return $this->pool->take()->get($key);
    }

    public function getAuth()
    {
        return $this->pool->take()->getAuth();
    }

    public function getBit($key, $offset)
    {
        return $this->pool->take()->getBit($key, $offset);
    }

    public function getDBNum()
    {
        return $this->pool->take()->getDBNum();
    }

    public function getHost()
    {
        return $this->pool->take()->getHost();
    }

    public function getLastError()
    {
        return $this->pool->take()->getLastError();
    }

    public function getMode()
    {
        return $this->pool->take()->getMode();
    }

    public function getOption($option)
    {
        return $this->pool->take()->getOption($option);
    }

    public function getPersistentID()
    {
        return $this->pool->take()->getPersistentID();
    }

    public function getPort()
    {
        return $this->pool->take()->getPort();
    }

    public function getRange($key, $start, $end)
    {
        return $this->pool->take()->getRange($key, $start, $end);
    }

    public function getReadTimeout()
    {
        return $this->pool->take()->getReadTimeout();
    }

    public function getSet($key, $value)
    {
        return $this->pool->take()->getSet($key, $value);
    }

    public function getTimeout()
    {
        return $this->pool->take()->getTimeout();
    }

    public function hDel($key, $member, ...$other_members)
    {
        return $this->pool->take()->hDel($key, $member, ...$other_members);
    }

    public function hExists($key, $member)
    {
        return $this->pool->take()->hExists($key, $member);
    }

    public function hGet($key, $member)
    {
        return $this->pool->take()->hGet($key, $member);
    }

    public function hGetAll($key)
    {
        return $this->pool->take()->hGetAll($key);
    }

    public function hIncrBy($key, $member, $value)
    {
        return $this->pool->take()->hIncrBy($key, $member, $value);
    }

    public function hIncrByFloat($key, $member, $value)
    {
        return $this->pool->take()->hIncrByFloat($key, $member, $value);
    }

    public function hKeys($key)
    {
        return $this->pool->take()->hKeys($key);
    }

    public function hLen($key)
    {
        return $this->pool->take()->hLen($key);
    }

    public function hMget($key, array $keys)
    {
        return $this->pool->take()->hMget($key, $keys);
    }

    public function hMset($key, array $pairs)
    {
        return $this->pool->take()->hMset($key, $pairs);
    }

    public function hSet($key, $member, $value)
    {
        return $this->pool->take()->hSet($key, $member, $value);
    }

    public function hSetNx($key, $member, $value)
    {
        return $this->pool->take()->hSetNx($key, $member, $value);
    }

    public function hStrLen($key, $member)
    {
        return $this->pool->take()->hStrLen($key, $member);
    }

    public function hVals($key)
    {
        return $this->pool->take()->hVals($key);
    }

    public function hscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->pool->take()->hscan($str_key, $i_iterator, $str_pattern, $i_count);
    }

    public function incr($key)
    {
        return $this->pool->take()->incr($key);
    }

    public function incrBy($key, $value)
    {
        return $this->pool->take()->incrBy($key, $value);
    }

    public function incrByFloat($key, $value)
    {
        return $this->pool->take()->incrByFloat($key, $value);
    }

    public function info($option = null)
    {
        return $this->pool->take()->info($option);
    }

    public function isConnected()
    {
        return $this->pool->take()->isConnected();
    }

    public function keys($pattern)
    {
        return $this->pool->take()->keys($pattern);
    }

    public function lInsert($key, $position, $pivot, $value)
    {
        return $this->pool->take()->lInsert($key, $position, $pivot, $value);
    }

    public function lLen($key)
    {
        return $this->pool->take()->lLen($key);
    }

    public function lPop($key)
    {
        return $this->pool->take()->lPop($key);
    }

    public function lPush($key, $value)
    {
        return $this->pool->take()->lPush($key, $value);
    }

    public function lPushx($key, $value)
    {
        return $this->pool->take()->lPushx($key, $value);
    }

    public function lSet($key, $index, $value)
    {
        return $this->pool->take()->lSet($key, $index, $value);
    }

    public function lastSave()
    {
        return $this->pool->take()->lastSave();
    }

    public function lindex($key, $index)
    {
        return $this->pool->take()->lindex($key, $index);
    }

    public function lrange($key, $start, $end)
    {
        return $this->pool->take()->lrange($key, $start, $end);
    }

    public function lrem($key, $value, $count)
    {
        return $this->pool->take()->lrem($key, $value, $count);
    }

    public function ltrim($key, $start, $stop)
    {
        return $this->pool->take()->ltrim($key, $start, $stop);
    }

    public function mget(array $keys)
    {
        return $this->pool->take()->mget($keys);
    }

    public function migrate($host, $port, $key, $db, $timeout, $copy = null, $replace = null)
    {
        return $this->pool->take()->migrate($host, $port, $key, $db, $timeout, $copy, $replace);
    }

    public function move($key, $dbindex)
    {
        return $this->pool->take()->move($key, $dbindex);
    }

    public function mset(array $pairs)
    {
        return $this->pool->take()->mset($pairs);
    }

    public function msetnx(array $pairs)
    {
        return $this->pool->take()->msetnx($pairs);
    }

    public function multi($mode = null)
    {
        return $this->pool->take()->multi($mode);
    }

    public function object($field, $key)
    {
        return $this->pool->take()->object($field, $key);
    }

    public function pconnect($host, $port = null, $timeout = null)
    {
        return $this->pool->take()->pconnect($host, $port, $timeout);
    }

    public function persist($key)
    {
        return $this->pool->take()->persist($key);
    }

    public function pexpire($key, $timestamp)
    {
        return $this->pool->take()->pexpire($key, $timestamp);
    }

    public function pexpireAt($key, $timestamp)
    {
        return $this->pool->take()->pexpireAt($key, $timestamp);
    }

    public function pfadd($key, array $elements)
    {
        return $this->pool->take()->pfadd($key, $elements);
    }

    public function pfcount($key)
    {
        return $this->pool->take()->pfcount($key);
    }

    public function pfmerge($dstkey, array $keys)
    {
        return $this->pool->take()->pfmerge($dstkey, $keys);
    }

    public function ping()
    {
        return $this->pool->take()->ping();
    }

    public function pipeline()
    {
        return $this->pool->take()->pipeline();
    }

    public function psetex($key, $expire, $value)
    {
        return $this->pool->take()->psetex($key, $expire, $value);
    }

    public function psubscribe(array $patterns, $callback)
    {
        return $this->pool->take()->psubscribe($patterns, $callback);
    }

    public function pttl($key)
    {
        return $this->pool->take()->pttl($key);
    }

    public function publish($channel, $message)
    {
        return $this->pool->take()->publish($channel, $message);
    }

    public function pubsub($cmd, ...$args)
    {
        return $this->pool->take()->pubsub($cmd, ...$args);
    }

    public function punsubscribe($pattern, ...$other_patterns)
    {
        return $this->pool->take()->punsubscribe($pattern, ...$other_patterns);
    }

    public function rPop($key)
    {
        return $this->pool->take()->rPop($key);
    }

    public function rPush($key, $value)
    {
        return $this->pool->take()->rPush($key, $value);
    }

    public function rPushx($key, $value)
    {
        return $this->pool->take()->rPushx($key, $value);
    }

    public function randomKey()
    {
        return $this->pool->take()->randomKey();
    }

    public function rawcommand($cmd, ...$args)
    {
        return $this->pool->take()->rawcommand($cmd, ...$args);
    }

    public function rename($key, $newkey)
    {
        return $this->pool->take()->rename($key, $newkey);
    }

    public function renameNx($key, $newkey)
    {
        return $this->pool->take()->renameNx($key, $newkey);
    }

    public function restore($ttl, $key, $value)
    {
        return $this->pool->take()->restore($ttl, $key, $value);
    }

    public function role()
    {
        return $this->pool->take()->role();
    }

    public function rpoplpush($src, $dst)
    {
        return $this->pool->take()->rpoplpush($src, $dst);
    }

    public function sAdd($key, $value)
    {
        return $this->pool->take()->sAdd($key, $value);
    }

    public function sAddArray($key, array $options)
    {
        return $this->pool->take()->sAddArray($key, $options);
    }

    public function sDiff($key, ...$other_keys)
    {
        return $this->pool->take()->sDiff($key, ...$other_keys);
    }

    public function sDiffStore($dst, $key, ...$other_keys)
    {
        return $this->pool->take()->sDiffStore($dst, $key, ...$other_keys);
    }

    public function sInter($key, ...$other_keys)
    {
        return $this->pool->take()->sInter($key, ...$other_keys);
    }

    public function sInterStore($dst, $key, ...$other_keys)
    {
        return $this->pool->take()->sInterStore($dst, $key, ...$other_keys);
    }

    public function sMembers($key)
    {
        return $this->pool->take()->sMembers($key);
    }

    public function sMove($src, $dst, $value)
    {
        return $this->pool->take()->sMove($src, $dst, $value);
    }

    public function sPop($key)
    {
        return $this->pool->take()->sPop($key);
    }

    public function sRandMember($key, $count = null)
    {
        return $this->pool->take()->sRandMember($key, $count);
    }

    public function sUnion($key, ...$other_keys)
    {
        return $this->pool->take()->sUnion($key, ...$other_keys);
    }

    public function sUnionStore($dst, $key, ...$other_keys)
    {
        return $this->pool->take()->sUnionStore($dst, $key, ...$other_keys);
    }

    public function save()
    {
        return $this->pool->take()->save();
    }

    public function scan(&$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->pool->take()->scan($i_iterator, $str_pattern, $i_count);
    }

    public function scard($key)
    {
        return $this->pool->take()->scard($key);
    }

    public function script($cmd, ...$args)
    {
        return $this->pool->take()->script($cmd, ...$args);
    }

    public function select($dbindex)
    {
        return $this->pool->take()->select($dbindex);
    }

    public function set($key, $value, $opts = null)
    {
        return $this->pool->take()->set($key, $value, $opts);
    }

    public function setBit($key, $offset, $value)
    {
        return $this->pool->take()->setBit($key, $offset, $value);
    }

    public function setOption($option, $value)
    {
        return $this->pool->take()->setOption($option, $value);
    }

    public function setRange($key, $offset, $value)
    {
        return $this->pool->take()->setRange($key, $offset, $value);
    }

    public function setex($key, $expire, $value)
    {
        return $this->pool->take()->setex($key, $expire, $value);
    }

    public function setnx($key, $value)
    {
        return $this->pool->take()->setnx($key, $value);
    }

    public function sismember($key, $value)
    {
        return $this->pool->take()->sismember($key, $value);
    }

    public function slaveof($host = null, $port = null)
    {
        return $this->pool->take()->slaveof($host, $port);
    }

    public function slowlog($arg, $option = null)
    {
        return $this->pool->take()->slowlog($arg, $option);
    }

    public function sort($key, array $options = null)
    {
        return $this->pool->take()->sort($key, $options);
    }

    public function sortAsc($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->pool->take()->sortAsc($key, $pattern, $get, $start, $end, $getList);
    }

    public function sortAscAlpha($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->pool->take()->sortAscAlpha($key, $pattern, $get, $start, $end, $getList);
    }

    public function sortDesc($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->pool->take()->sortDesc($key, $pattern, $get, $start, $end, $getList);
    }

    public function sortDescAlpha($key, $pattern = null, $get = null, $start = null, $end = null, $getList = null)
    {
        return $this->pool->take()->sortDescAlpha($key, $pattern, $get, $start, $end, $getList);
    }

    public function srem($key, $member, ...$other_members)
    {
        return $this->pool->take()->srem($key, $member, ...$other_members);
    }

    public function sscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->pool->take()->sscan($str_key, $i_iterator, $str_pattern, $i_count);
    }

    public function strlen($key)
    {
        return $this->pool->take()->strlen($key);
    }

    public function subscribe(array $channels, $callback)
    {
        return $this->pool->take()->subscribe($channels, $callback);
    }

    public function swapdb($srcdb, $dstdb)
    {
        return $this->pool->take()->swapdb($srcdb, $dstdb);
    }

    public function time()
    {
        return $this->pool->take()->time();
    }

    public function ttl($key)
    {
        return $this->pool->take()->ttl($key);
    }

    public function type($key)
    {
        return $this->pool->take()->type($key);
    }

    public function unlink($key, ...$other_keys)
    {
        return $this->pool->take()->unlink($key, ...$other_keys);
    }

    public function unsubscribe($channel, ...$other_channels)
    {
        return $this->pool->take()->unsubscribe($channel, ...$other_channels);
    }

    public function unwatch()
    {
        return $this->pool->take()->unwatch();
    }

    public function wait($numslaves, $timeout)
    {
        return $this->pool->take()->wait($numslaves, $timeout);
    }

    public function watch($key, ...$other_keys)
    {
        return $this->pool->take()->watch($key, ...$other_keys);
    }

    public function xack($str_key, $str_group, array $arr_ids)
    {
        return $this->pool->take()->xack($str_key, $str_group, $arr_ids);
    }

    public function xadd($str_key, $str_id, array $arr_fields, $i_maxlen = null, $boo_approximate = null)
    {
        return $this->pool->take()->xadd($str_key, $str_id, $arr_fields, $i_maxlen, $boo_approximate);
    }

    public function xclaim($str_key, $str_group, $str_consumer, $i_min_idle, array $arr_ids, array $arr_opts = null)
    {
        return $this->pool->take()->xclaim($str_key, $str_group, $str_consumer, $i_min_idle, $arr_ids, $arr_opts);
    }

    public function xdel($str_key, array $arr_ids)
    {
        return $this->pool->take()->xdel($str_key, $arr_ids);
    }

    public function xgroup($str_operation, $str_key = null, $str_arg1 = null, $str_arg2 = null, $str_arg3 = null)
    {
        return $this->pool->take()->xgroup($str_operation, $str_key, $str_arg1, $str_arg2, $str_arg3);
    }

    public function xinfo($str_cmd, $str_key = null, $str_group = null)
    {
        return $this->pool->take()->xinfo($str_cmd, $str_key, $str_group);
    }

    public function xlen($key)
    {
        return $this->pool->take()->xlen($key);
    }

    public function xpending($str_key, $str_group, $str_start = null, $str_end = null, $i_count = null, $str_consumer = null)
    {
        return $this->pool->take()->xpending($str_key, $str_group, $str_start, $str_end, $i_count, $str_consumer);
    }

    public function xrange($str_key, $str_start, $str_end, $i_count = null)
    {
        return $this->pool->take()->xrange($str_key, $str_start, $str_end, $i_count);
    }

    public function xread(array $arr_streams, $i_count = null, $i_block = null)
    {
        return $this->pool->take()->xread($arr_streams, $i_count, $i_block);
    }

    public function xreadgroup($str_group, $str_consumer, array $arr_streams, $i_count = null, $i_block = null)
    {
        return $this->pool->take()->xreadgroup($str_group, $str_consumer, $arr_streams, $i_count, $i_block);
    }

    public function xrevrange($str_key, $str_start, $str_end, $i_count = null)
    {
        return $this->pool->take()->xrevrange($str_key, $str_start, $str_end, $i_count);
    }

    public function xtrim($str_key, $i_maxlen, $boo_approximate = null)
    {
        return $this->pool->take()->xtrim($str_key, $i_maxlen, $boo_approximate);
    }

    public function zAdd($key, $score, $value, ...$extra_args)
    {
        return $this->pool->take()->zAdd($key, $score, $value, ...$extra_args);
    }

    public function zCard($key)
    {
        return $this->pool->take()->zCard($key);
    }

    public function zCount($key, $min, $max)
    {
        return $this->pool->take()->zCount($key, $min, $max);
    }

    public function zIncrBy($key, $value, $member)
    {
        return $this->pool->take()->zIncrBy($key, $value, $member);
    }

    public function zLexCount($key, $min, $max)
    {
        return $this->pool->take()->zLexCount($key, $min, $max);
    }

    public function zPopMax($key)
    {
        return $this->pool->take()->zPopMax($key);
    }

    public function zPopMin($key)
    {
        return $this->pool->take()->zPopMin($key);
    }

    public function zRange($key, $start, $end, $scores = null)
    {
        return $this->pool->take()->zRange($key, $start, $end, $scores);
    }

    public function zRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->pool->take()->zRangeByLex($key, $min, $max, $offset, $limit);
    }

    public function zRangeByScore($key, $start, $end, array $options = null)
    {
        return $this->pool->take()->zRangeByScore($key, $start, $end, $options);
    }

    public function zRank($key, $member)
    {
        return $this->pool->take()->zRank($key, $member);
    }

    public function zRem($key, $member, ...$other_members)
    {
        return $this->pool->take()->zRem($key, $member, ...$other_members);
    }

    public function zRemRangeByLex($key, $min, $max)
    {
        return $this->pool->take()->zRemRangeByLex($key, $min, $max);
    }

    public function zRemRangeByRank($key, $start, $end)
    {
        return $this->pool->take()->zRemRangeByRank($key, $start, $end);
    }

    public function zRemRangeByScore($key, $min, $max)
    {
        return $this->pool->take()->zRemRangeByScore($key, $min, $max);
    }

    public function zRevRange($key, $start, $end, $scores = null)
    {
        return $this->pool->take()->zRevRange($key, $start, $end, $scores);
    }

    public function zRevRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->pool->take()->zRevRangeByLex($key, $min, $max, $offset, $limit);
    }

    public function zRevRangeByScore($key, $start, $end, array $options = null)
    {
        return $this->pool->take()->zRevRangeByScore($key, $start, $end, $options);
    }

    public function zRevRank($key, $member)
    {
        return $this->pool->take()->zRevRank($key, $member);
    }

    public function zScore($key, $member)
    {
        return $this->pool->take()->zScore($key, $member);
    }

    public function zinterstore($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->pool->take()->zinterstore($key, $keys, $weights, $aggregate);
    }

    public function zscan($str_key, &$i_iterator, $str_pattern = null, $i_count = null)
    {
        return $this->pool->take()->zscan($str_key, $i_iterator, $str_pattern, $i_count);
    }

    public function zunionstore($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->pool->take()->zunionstore($key, $keys, $weights, $aggregate);
    }

    public function delete($key, ...$other_keys)
    {
        return $this->pool->take()->delete($key, ...$other_keys);
    }

    public function evaluate($script, $args = null, $num_keys = null)
    {
        return $this->pool->take()->evaluate($script, $args, $num_keys);
    }

    public function evaluateSha($script_sha, $args = null, $num_keys = null)
    {
        return $this->pool->take()->evaluateSha($script_sha, $args, $num_keys);
    }

    public function getKeys($pattern)
    {
        return $this->pool->take()->getKeys($pattern);
    }

    public function getMultiple(array $keys)
    {
        return $this->pool->take()->getMultiple($keys);
    }

    public function lGet($key, $index)
    {
        return $this->pool->take()->lGet($key, $index);
    }

    public function lGetRange($key, $start, $end)
    {
        return $this->pool->take()->lGetRange($key, $start, $end);
    }

    public function lRemove($key, $value, $count)
    {
        return $this->pool->take()->lRemove($key, $value, $count);
    }

    public function lSize($key)
    {
        return $this->pool->take()->lSize($key);
    }

    public function listTrim($key, $start, $stop)
    {
        return $this->pool->take()->listTrim($key, $start, $stop);
    }

    public function open($host, $port = null, $timeout = null, $retry_interval = null)
    {
        return $this->pool->take()->open($host, $port, $timeout, $retry_interval);
    }

    public function popen($host, $port = null, $timeout = null)
    {
        return $this->pool->take()->popen($host, $port, $timeout);
    }

    public function renameKey($key, $newkey)
    {
        return $this->pool->take()->renameKey($key, $newkey);
    }

    public function sContains($key, $value)
    {
        return $this->pool->take()->sContains($key, $value);
    }

    public function sGetMembers($key)
    {
        return $this->pool->take()->sGetMembers($key);
    }

    public function sRemove($key, $member, ...$other_members)
    {
        return $this->pool->take()->sRemove($key, $member, ...$other_members);
    }

    public function sSize($key)
    {
        return $this->pool->take()->sSize($key);
    }

    public function sendEcho($msg)
    {
        return $this->pool->take()->sendEcho($msg);
    }

    public function setTimeout($key, $timeout)
    {
        return $this->pool->take()->setTimeout($key, $timeout);
    }

    public function substr($key, $start, $end)
    {
        return $this->pool->take()->substr($key, $start, $end);
    }

    public function zDelete($key, $member, ...$other_members)
    {
        return $this->pool->take()->zDelete($key, $member, ...$other_members);
    }

    public function zDeleteRangeByRank($key, $min, $max)
    {
        return $this->pool->take()->zDeleteRangeByRank($key, $min, $max);
    }

    public function zDeleteRangeByScore($key, $min, $max)
    {
        return $this->pool->take()->zDeleteRangeByScore($key, $min, $max);
    }

    public function zInter($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->pool->take()->zInter($key, $keys, $weights, $aggregate);
    }

    public function zRemove($key, $member, ...$other_members)
    {
        return $this->pool->take()->zRemove($key, $member, ...$other_members);
    }

    public function zRemoveRangeByScore($key, $min, $max)
    {
        return $this->pool->take()->zRemoveRangeByScore($key, $min, $max);
    }

    public function zReverseRange($key, $start, $end, $scores = null)
    {
        return $this->pool->take()->zReverseRange($key, $start, $end, $scores);
    }

    public function zSize($key)
    {
        return $this->pool->take()->zSize($key);
    }

    public function zUnion($key, array $keys, ?array $weights = null, $aggregate = null)
    {
        return $this->pool->take()->zUnion($key, $keys, $weights, $aggregate);
    }
}
