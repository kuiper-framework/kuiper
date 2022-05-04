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

namespace kuiper\swoole\constants;

final class ServerSetting 
{
    public const REACTOR_NUM = 'reactor_num';
    public const WORKER_NUM = 'worker_num';
    public const MAX_REQUEST = 'max_request';
    public const MAX_CONN = 'max_conn';
    public const MAX_CONNECTION = 'max_connection';
    public const TASK_WORKER_NUM = 'task_worker_num';
    public const TASK_IPC_MODE = 'task_ipc_mode';
    public const TASK_MAX_REQUEST = 'task_max_request';
    public const TASK_TMPDIR = 'task_tmpdir';
    public const TASK_ENABLE_COROUTINE = 'task_enable_coroutine';
    public const TASK_USE_OBJECT = 'task_use_object';
    public const DISPATCH_MODE = 'dispatch_mode';
    public const DISPATCH_FUNC = 'dispatch_func';
    public const MESSAGE_QUEUE_KEY = 'message_queue_key';
    public const DAEMONIZE = 'daemonize';
    public const BACKLOG = 'backlog';
    public const LOG_FILE = 'log_file';
    public const LOG_LEVEL = 'log_level';
    public const HEARTBEAT_CHECK_INTERVAL = 'heartbeat_check_interval';
    public const HEARTBEAT_IDLE_TIME = 'heartbeat_idle_time';
    public const OPEN_EOF_CHECK = 'open_eof_check';
    public const OPEN_EOF_SPLIT = 'open_eof_split';
    public const PACKAGE_EOF = 'package_eof';
    public const OPEN_LENGTH_CHECK = 'open_length_check';
    public const PACKAGE_LENGTH_TYPE = 'package_length_type';
    public const PACKAGE_LENGTH_FUNC = 'package_length_func';
    public const PACKAGE_MAX_LENGTH = 'package_max_length';
    public const OPEN_CPU_AFFINITY = 'open_cpu_affinity';
    public const CPU_AFFINITY_IGNORE = 'cpu_affinity_ignore';
    public const OPEN_TCP_NODELAY = 'open_tcp_nodelay';
    public const TCP_DEFER_ACCEPT = 'tcp_defer_accept';
    public const SSL_CERT_FILE = 'ssl_cert_file';
    public const SSL_METHOD = 'ssl_method';
    public const SSL_CIPHERS = 'ssl_ciphers';
    public const USER = 'user';
    public const GROUP = 'group';
    public const CHROOT = 'chroot';
    public const PID_FILE = 'pid_file';
    public const PIPE_BUFFER_SIZE = 'pipe_buffer_size';
    public const BUFFER_OUTPUT_SIZE = 'buffer_output_size';
    public const SOCKET_BUFFER_SIZE = 'socket_buffer_size';
    public const ENABLE_UNSAFE_EVENT = 'enable_unsafe_event';
    public const DISCARD_TIMEOUT_REQUEST = 'discard_timeout_request';
    public const ENABLE_REUSE_PORT = 'enable_reuse_port';
    public const ENABLE_DELAY_RECEIVE = 'enable_delay_receive';
    public const OPEN_HTTP_PROTOCOL = 'open_http_protocol';
    public const OPEN_HTTP2_PROTOCOL = 'open_http2_protocol';
    public const OPEN_WEBSOCKET_PROTOCOL = 'open_websocket_protocol';
    public const OPEN_MQTT_PROTOCOL = 'open_mqtt_protocol';
    public const OPEN_WEBSOCKET_CLOSE_FRAME = 'open_websocket_close_frame';
    public const RELOAD_ASYNC = 'reload_async';
    public const TCP_FASTOPEN = 'tcp_fastopen';
    public const REQUEST_SLOWLOG_FILE = 'request_slowlog_file';
    public const ENABLE_COROUTINE = 'enable_coroutine';
    public const MAX_COROUTINE = 'max_coroutine';
    public const SSL_VERIFY_PEER = 'ssl_verify_peer';
    public const MAX_WAIT_TIME = 'max_wait_time';
    public const PACKAGE_LENGTH_OFFSET = 'package_length_offset';
    public const PACKAGE_BODY_OFFSET = 'package_body_offset';

    public static function type(string $name): string
    {
        return match ($name) {
            self::REACTOR_NUM, self::WORKER_NUM, self::MAX_REQUEST,
            self::MAX_CONN, self::MAX_CONNECTION, self::TASK_WORKER_NUM,
            self::TASK_IPC_MODE, self::TASK_MAX_REQUEST, self::DISPATCH_MODE,
            self::BACKLOG, self::LOG_LEVEL, self::HEARTBEAT_CHECK_INTERVAL,
            self::HEARTBEAT_IDLE_TIME, self::PACKAGE_MAX_LENGTH, self::OPEN_CPU_AFFINITY,
            self::TCP_DEFER_ACCEPT, self::PIPE_BUFFER_SIZE, self::BUFFER_OUTPUT_SIZE,
            self::SOCKET_BUFFER_SIZE, self::MAX_COROUTINE, self::MAX_WAIT_TIME,
            self::PACKAGE_BODY_OFFSET, self::PACKAGE_LENGTH_OFFSET => 'int',
            self::TASK_ENABLE_COROUTINE, self::TASK_USE_OBJECT,
            self::DAEMONIZE, self::OPEN_EOF_CHECK, self::OPEN_EOF_SPLIT,
            self::OPEN_LENGTH_CHECK, self::OPEN_TCP_NODELAY, self::ENABLE_UNSAFE_EVENT,
            self::DISCARD_TIMEOUT_REQUEST, self::ENABLE_REUSE_PORT, self::ENABLE_DELAY_RECEIVE,
            self::OPEN_HTTP_PROTOCOL, self::OPEN_HTTP2_PROTOCOL, self::OPEN_WEBSOCKET_PROTOCOL,
            self::OPEN_MQTT_PROTOCOL, self::OPEN_WEBSOCKET_CLOSE_FRAME, self::RELOAD_ASYNC,
            self::TCP_FASTOPEN, self::ENABLE_COROUTINE, self::SSL_VERIFY_PEER => 'bool',
            self::DISPATCH_FUNC, self::PACKAGE_LENGTH_FUNC => 'callable',
            self::CPU_AFFINITY_IGNORE => 'array',
            default => 'string'
        };
    }

    public static function has(string $name): bool
    {
        $constantName = __CLASS__ . "::" . strtoupper($name);
        return defined($constantName) && constant($constantName) === $name;
    }
}
