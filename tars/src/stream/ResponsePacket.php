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

use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\exception\ErrorCode;
use kuiper\tars\type\MapType;
use kuiper\tars\type\VectorType;

final class ResponsePacket
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
    public $iRequestId = 0;

    /**
     * @var int|null
     */
    public $iMessageType = TarsConst::MESSAGE_TYPE;

    /**
     * @var int|null
     */
    public $iRet = ErrorCode::SERVER_SUCCESS;

    /**
     * @var string
     */
    public $sBuffer;

    /**
     * @var string[]|null
     */
    public $status = [];

    /**
     * @var string|null
     */
    public $sResultDesc;

    /**
     * @var string[]|null
     */
    public $context = [];

    public static function createFromRequest(TarsRequestInterface $request): self
    {
        $packet = new self();
        $packet->iRet = ErrorCode::SERVER_SUCCESS;
        $packet->iRequestId = $request->getRequestId();
        $packet->iVersion = $request->getVersion();
        $packet->cPacketType = $request->getPacketType();
        $packet->iMessageType = $request->getMessageType();

        return $packet;
    }

    public function encode(): TarsOutputStream
    {
        $os = new TarsOutputStream(true);
        $os->writeInt16(1, $this->iVersion);
        $os->writeInt8(2, $this->cPacketType ?? TarsConst::PACKET_TYPE);
        if (TarsConst::VERSION === $this->iVersion) {
            $this->context[TarsConst::RESULT_CODE] = $this->iRet;
            $this->context[TarsConst::RESULT_DESC] = $this->sResultDesc;
            $os->writeInt32(3, $this->iMessageType ?? TarsConst::TIMEOUT);
            $os->writeInt32(4, $this->iRequestId);
            $os->writeString(5, '');
            $os->writeString(6, '');
            $os->writeVector(7, $this->sBuffer, VectorType::byteVector());
            $os->writeInt32(8, 0);
            $os->writeMap(9, $this->context, MapType::stringMap());
            $os->writeMap(10, $this->status, MapType::stringMap());
        } else {
            $os->writeInt32(3, $this->iRequestId);
            $os->writeInt32(4, $this->iMessageType ?? TarsConst::TIMEOUT);
            $os->writeInt32(5, $this->iRet);
            $os->writeVector(6, $this->sBuffer, VectorType::byteVector());
            $os->writeMap(7, $this->status, MapType::stringMap());
            $os->writeString(8, $this->sResultDesc);
            if (null !== $this->context) {
                $os->writeMap(9, $this->context, MapType::stringMap());
            }
        }

        return $os;
    }

    public static function decode(string $data): ResponsePacket
    {
        $packet = new self();
        $is = new TarsInputStream(substr($data, 4));
        $packet->iVersion = $is->readInt16(1, true);
        $packet->cPacketType = $is->readInt8(2, true);
        if (TarsConst::VERSION === $packet->iVersion) {
            $packet->iMessageType = $is->readInt32(3, true);
            $packet->iRequestId = $is->readInt32(4, true);
            $is->readString(5, true);
            $is->readString(6, true);
            $packet->sBuffer = $is->readVector(7, true, VectorType::byteVector());
            $is->readInt32(8, true);
            $packet->context = $is->readMap(9, true, MapType::stringMap());
            $packet->status = $is->readMap(10, true, MapType::stringMap());
            $packet->iRet = (int) ($packet->context[TarsConst::RESULT_CODE] ?? ErrorCode::SERVER_SUCCESS);
            $packet->sResultDesc = $packet->context[TarsConst::RESULT_DESC] ?? '';
        } else {
            $packet->cPacketType = $is->readInt8(2, true);
            $packet->iRequestId = $is->readInt32(3, true);
            $packet->iMessageType = $is->readInt32(4, true);
            $packet->iRet = $is->readInt32(5, true);
            $packet->sBuffer = $is->readVector(6, true, VectorType::byteVector());
            $packet->status = $is->readMap(7, true, MapType::stringMap());
            $packet->sResultDesc = $is->readString(8, true);
            $packet->context = $is->readMap(9, false, MapType::stringMap()) ?? [];
        }

        return $packet;
    }
}
