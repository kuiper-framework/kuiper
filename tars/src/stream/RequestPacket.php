<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\tars\type\MapType;
use kuiper\tars\type\VectorType;

final class RequestPacket
{
    /**
     * @var int|null
     */
    public $iVersion = TarsConst::VERSION;

    /**
     * @var int|null
     */
    public $cPacketType = TarsConst::PACKET_TYPE;

    /**
     * @var int|null
     */
    public $iMessageType = TarsConst::MESSAGE_TYPE;

    /**
     * @var int|null
     */
    public $iRequestId = 0;

    /**
     * @var string|null
     */
    public $sServantName = '';

    /**
     * @var string|null
     */
    public $sFuncName = '';

    /**
     * @var string|null
     */
    public $sBuffer;

    /**
     * @var int|null
     */
    public $iTimeout = TarsConst::TIMEOUT;

    /**
     * @var string[]|null
     */
    public $context = [];

    /**
     * @var string[]|null
     */
    public $status = [];

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
