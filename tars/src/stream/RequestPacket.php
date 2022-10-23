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

namespace kuiper\tars\stream;

use kuiper\tars\type\MapType;
use kuiper\tars\type\VectorType;

final class RequestPacket
{
    public int $iVersion = TarsConst::VERSION;

    public int $cPacketType = TarsConst::PACKET_TYPE;

    public int $iMessageType = TarsConst::MESSAGE_TYPE;

    public int $iRequestId = 0;

    public string $sServantName = '';

    public string $sFuncName = '';

    public string $sBuffer;

    public int $iTimeout = TarsConst::TIMEOUT;

    /**
     * 请求响应结果.
     *
     * @var string[]
     */
    public array $status = [];

    /**
     * 请求上下文.
     *
     * @var string[]
     */
    public array $context = [];

    /**
     * @throws \kuiper\tars\exception\TarsStreamException
     */
    public function encode(): TarsOutputStream
    {
        $os = new TarsOutputStream(true);
        $os->writeInt16(1, $this->iVersion ?? TarsConst::VERSION);
        $os->writeInt8(2, $this->cPacketType ?? TarsConst::PACKET_TYPE);
        $os->writeInt32(3, $this->iMessageType ?? TarsConst::TIMEOUT);
        $os->writeInt32(4, $this->iRequestId);
        $os->writeString(5, $this->sServantName);
        $os->writeString(6, $this->sFuncName);
        $os->writeVector(7, $this->sBuffer, VectorType::byteVector());
        $os->writeInt32(8, $this->iTimeout);
        $os->writeMap(9, $this->context, MapType::stringMap());
        $os->writeMap(10, $this->status, MapType::stringMap());

        return $os;
    }

    public static function decode(string $data): self
    {
        $packet = new self();
        $os = new TarsInputStream(substr($data, 4));
        $packet->iVersion = $os->readInt16(1, true);
        $packet->cPacketType = $os->readInt8(2, true);
        $packet->iMessageType = $os->readInt32(3, true);
        $packet->iRequestId = $os->readInt32(4, true);
        $packet->sServantName = $os->readString(5, true);
        $packet->sFuncName = $os->readString(6, true);
        $packet->sBuffer = $os->readVector(7, true, VectorType::byteVector());
        $packet->iTimeout = $os->readInt32(8, true);
        $packet->context = $os->readMap(9, true, MapType::stringMap());
        $packet->status = $os->readMap(10, true, MapType::stringMap());

        return $packet;
    }
}
