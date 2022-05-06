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

namespace kuiper\rpc\exception;

class ErrorCode
{
    // 错误码定义（需要从扩展开始规划）
    public const SOCKET_SET_NONBLOCK_FAILED = -1002; // socket设置非阻塞失败
    public const SOCKET_SEND_FAILED = -1003; // socket发送失败
    public const SOCKET_RECEIVE_FAILED = -1004; // socket接收失败
    public const SOCKET_SELECT_TIMEOUT = -1005; // socket的select超时，也可以认为是svr超时
    public const SOCKET_TIMEOUT = -1006; // socket超时，一般是svr后台没回包，或者seq错误
    public const SOCKET_CONNECT_FAILED = -1007; // socket tcp 连接失败
    public const SOCKET_CLOSED = -1008; // socket tcp 服务端连接关闭
    public const SOCKET_CREATE_FAILED = -1009;

    public const UNKNOWN = 99999;
    public const INVALID_ARGUMENT = 100000;
    public const INVALID_ENDPOINT = 100001;

    public static function getMessage(int $errorCode): string
    {
        return match ($errorCode) {
            self::SOCKET_SET_NONBLOCK_FAILED => 'socket设置非阻塞失败',
            self::SOCKET_SEND_FAILED => 'socket发送失败',
            self::SOCKET_RECEIVE_FAILED => 'socket接收失败',
            self::SOCKET_SELECT_TIMEOUT => 'socket的select超时，也可以认为是服务超时',
            self::SOCKET_TIMEOUT => 'socket超时，一般是后台服务没回包，或者seq错误',
            self::SOCKET_CONNECT_FAILED => 'socket tcp 连接失败',
            self::SOCKET_CLOSED => 'socket tcp 服务端连接关闭',
            self::SOCKET_CREATE_FAILED => 'socket 创建失败',
            self::INVALID_ARGUMENT => '参数不正确',
            self::INVALID_ENDPOINT => '服务地址不正确',
            default => '未定义异常'
        };
    }
}
