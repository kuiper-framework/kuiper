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

enum ServerSetting : string
{
    case REACTOR_NUM = 'reactor_num';
    case WORKER_NUM = 'worker_num';
    case MAX_REQUEST = 'max_request';
    case MAX_CONN = 'max_conn';
    case MAX_CONNECTION = 'max_connection';
    case TASK_WORKER_NUM = 'task_worker_num';
    case TASK_IPC_MODE = 'task_ipc_mode';
    case TASK_MAX_REQUEST = 'task_max_request';
    case TASK_TMPDIR = 'task_tmpdir';
    case TASK_ENABLE_COROUTINE = 'task_enable_coroutine';
    case TASK_USE_OBJECT = 'task_use_object';
    case DISPATCH_MODE = 'dispatch_mode';
    case DISPATCH_FUNC = 'dispatch_func';
    case MESSAGE_QUEUE_KEY = 'message_queue_key';
    case DAEMONIZE = 'daemonize';
    case BACKLOG = 'backlog';
    case LOG_FILE = 'log_file';
    case LOG_LEVEL = 'log_level';
    case HEARTBEAT_CHECK_INTERVAL = 'heartbeat_check_interval';
    case HEARTBEAT_IDLE_TIME = 'heartbeat_idle_time';
    case OPEN_EOF_CHECK = 'open_eof_check';
    case OPEN_EOF_SPLIT = 'open_eof_split';
    case PACKAGE_EOF = 'package_eof';
    case OPEN_LENGTH_CHECK = 'open_length_check';
    case PACKAGE_LENGTH_TYPE = 'package_length_type';
    case PACKAGE_LENGTH_FUNC = 'package_length_func';
    case PACKAGE_MAX_LENGTH = 'package_max_length';
    case OPEN_CPU_AFFINITY = 'open_cpu_affinity';
    case CPU_AFFINITY_IGNORE = 'cpu_affinity_ignore';
    case OPEN_TCP_NODELAY = 'open_tcp_nodelay';
    case TCP_DEFER_ACCEPT = 'tcp_defer_accept';
    case SSL_CERT_FILE = 'ssl_cert_file';
    case SSL_METHOD = 'ssl_method';
    case SSL_CIPHERS = 'ssl_ciphers';
    case USER = 'user';
    case GROUP = 'group';
    case CHROOT = 'chroot';
    case PID_FILE = 'pid_file';
    case PIPE_BUFFER_SIZE = 'pipe_buffer_size';
    case BUFFER_OUTPUT_SIZE = 'buffer_output_size';
    case SOCKET_BUFFER_SIZE = 'socket_buffer_size';
    case ENABLE_UNSAFE_EVENT = 'enable_unsafe_event';
    case DISCARD_TIMEOUT_REQUEST = 'discard_timeout_request';
    case ENABLE_REUSE_PORT = 'enable_reuse_port';
    case ENABLE_DELAY_RECEIVE = 'enable_delay_receive';
    case OPEN_HTTP_PROTOCOL = 'open_http_protocol';
    case OPEN_HTTP2_PROTOCOL = 'open_http2_protocol';
    case OPEN_WEBSOCKET_PROTOCOL = 'open_websocket_protocol';
    case OPEN_MQTT_PROTOCOL = 'open_mqtt_protocol';
    case OPEN_WEBSOCKET_CLOSE_FRAME = 'open_websocket_close_frame';
    case RELOAD_ASYNC = 'reload_async';
    case TCP_FASTOPEN = 'tcp_fastopen';
    case REQUEST_SLOWLOG_FILE = 'request_slowlog_file';
    case ENABLE_COROUTINE = 'enable_coroutine';
    case MAX_COROUTINE = 'max_coroutine';
    case SSL_VERIFY_PEER = 'ssl_verify_peer';
    case MAX_WAIT_TIME = 'max_wait_time';
    case PACKAGE_LENGTH_OFFSET = 'package_length_offset';
    case PACKAGE_BODY_OFFSET = 'package_body_offset';

    public function type(): string
    {
        return match ($this) {
            self::REACTOR_NUM => 'int',
            self::WORKER_NUM => 'int',
            self::MAX_REQUEST => 'int',
            self::MAX_CONN => 'int',
            self::MAX_CONNECTION => 'int',
            self::TASK_WORKER_NUM => 'int',
            self::TASK_IPC_MODE => 'int',
            self::TASK_MAX_REQUEST => 'int',
            self::TASK_TMPDIR => 'string',
            self::TASK_ENABLE_COROUTINE => 'bool',
            self::TASK_USE_OBJECT => 'bool',
            self::DISPATCH_MODE => 'int',
            self::DISPATCH_FUNC => 'callable',
            self::MESSAGE_QUEUE_KEY => 'string',
            self::DAEMONIZE => 'bool',
            self::BACKLOG => 'int',
            self::LOG_FILE => 'string',
            self::LOG_LEVEL => 'int',
            self::HEARTBEAT_CHECK_INTERVAL => 'int',
            self::HEARTBEAT_IDLE_TIME => 'int',
            self::OPEN_EOF_CHECK => 'bool',
            self::OPEN_EOF_SPLIT => 'bool',
            self::PACKAGE_EOF => 'string',
            self::OPEN_LENGTH_CHECK => 'bool',
            self::PACKAGE_LENGTH_TYPE => 'string',
            self::PACKAGE_LENGTH_FUNC => 'callable',
            self::PACKAGE_MAX_LENGTH => 'int',
            self::OPEN_CPU_AFFINITY => 'int',
            self::CPU_AFFINITY_IGNORE => 'array',
            self::OPEN_TCP_NODELAY => 'bool',
            self::TCP_DEFER_ACCEPT => 'int',
            self::SSL_CERT_FILE => 'string',
            self::SSL_METHOD => 'string',
            self::SSL_CIPHERS => 'string',
            self::USER => 'string',
            self::GROUP => 'string',
            self::CHROOT => 'string',
            self::PID_FILE => 'string',
            self::PIPE_BUFFER_SIZE => 'int',
            self::BUFFER_OUTPUT_SIZE => 'int',
            self::SOCKET_BUFFER_SIZE => 'int',
            self::ENABLE_UNSAFE_EVENT => 'bool',
            self::DISCARD_TIMEOUT_REQUEST => 'bool',
            self::ENABLE_REUSE_PORT => 'bool',
            self::ENABLE_DELAY_RECEIVE => 'bool',
            self::OPEN_HTTP_PROTOCOL => 'bool',
            self::OPEN_HTTP2_PROTOCOL => 'bool',
            self::OPEN_WEBSOCKET_PROTOCOL => 'bool',
            self::OPEN_MQTT_PROTOCOL => 'bool',
            self::OPEN_WEBSOCKET_CLOSE_FRAME => 'bool',
            self::RELOAD_ASYNC => 'bool',
            self::TCP_FASTOPEN => 'bool',
            self::REQUEST_SLOWLOG_FILE => 'string',
            self::ENABLE_COROUTINE => 'bool',
            self::MAX_COROUTINE => 'int',
            self::SSL_VERIFY_PEER => 'bool',
            self::MAX_WAIT_TIME => 'int',
            self::PACKAGE_BODY_OFFSET => 'int',
            self::PACKAGE_LENGTH_OFFSET => 'int',
            default => 'string',
        };
    }
}
