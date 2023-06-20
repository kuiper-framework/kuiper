<?php

declare(strict_types=1);

namespace kuiper\logger\processor;

use JsonException;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class JsonUnescapeUnicode implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $result = preg_replace_callback('/\\\\u[0-9a-f]{4,}/', static function ($matches) {
            $str = '"'.$matches[0].'"';
            try {
                return json_decode($str, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return $matches[0];
            }
        }, $record->message);

        return $record->with(message: $result);
    }
}
