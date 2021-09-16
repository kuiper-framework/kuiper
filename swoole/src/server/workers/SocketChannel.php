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

namespace kuiper\swoole\server\workers;

class SocketChannel
{
    /**
     * @var resource
     */
    private $child;

    /**
     * @var resource
     */
    private $parent;

    /**
     * @var resource|null
     */
    private $socket;

    /**
     * Channel constructor.
     */
    public function __construct()
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if (!$sockets) {
            throw new \RuntimeException('Cannot create socket pair');
        }
        [$this->child, $this->parent] = $sockets;
    }

    public function child(): void
    {
        fclose($this->parent);
        $this->socket = $this->child;
//        unset($this->parent, $this->child);
    }

    public function parent(): void
    {
        fclose($this->child);
        $this->socket = $this->parent;
//        unset($this->parent, $this->child);
    }

    public function close(): void
    {
        if (null !== $this->socket) {
            fclose($this->socket);
            unset($this->socket);
        }
    }

    public function isActive(): bool
    {
        return isset($this->socket);
    }

    /**
     * @param mixed $data
     */
    public function send($data): void
    {
        if (!$this->isActive()) {
            throw new \InvalidArgumentException('Cannot send on closed channel');
        }
        $serialized = serialize($data);
        $hdr = pack('N', strlen($serialized));    // 4 byte length
        $buffer = $hdr.$serialized;
        $total = strlen($buffer);
        while (true) {
            $sent = fwrite($this->socket, $buffer);
            if (empty($sent)) {
                //$error = socket_strerror(socket_last_error());
                break;
            }
            if ($sent >= $total) {
                break;
            }
            $total -= $sent;
            $buffer = substr($buffer, $sent);
        }
    }

    /**
     * @return mixed|null
     */
    public function receive(?int $timeout = null)
    {
        $select = self::select([$this], $timeout);
        if (!empty($select)) {
            return $select[0]->read();
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function read()
    {
        if (!$this->isActive()) {
            throw new \InvalidArgumentException('cannot read on closed channel');
        }
        // read 4 byte length first
        $hdr = '';
        do {
            $read = fread($this->socket, 4 - strlen($hdr));
            if (false === $read || '' === $read) {
                return null;
            }
            $hdr .= $read;
        } while (strlen($hdr) < 4);

        [$len] = array_values(unpack('N', $hdr));

        // read the full buffer
        $buffer = '';
        do {
            $read = fread($this->socket, $len - strlen($buffer));
            if (false === $read || '' === $read) {
                return null;
            }
            $buffer .= $read;
        } while (strlen($buffer) < $len);

        return unserialize($buffer);
    }

    /**
     * @param self[] $channels
     *
     * @return self[]
     */
    public static function select(array $channels, ?int $timeout = null): array
    {
        $read = [];
        foreach ($channels as $i => $channel) {
            if ($channel->isActive()) {
                $read[$i] = $channel->socket;
            }
        }
        $write = $except = null;
        if (!empty($read) && stream_select($read, $write, $except, $timeout) && !empty($read)) {
            $ready = [];
            foreach ($read as $i => $fd) {
                $ready[] = $channels[$i];
            }

            return $ready;
        }

        return [];
    }
}
