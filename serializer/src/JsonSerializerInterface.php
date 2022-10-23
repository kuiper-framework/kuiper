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

namespace kuiper\serializer;

use JsonException;

interface JsonSerializerInterface
{
    /**
     * Serializes data into json.
     *
     * @param array|object $data
     * @param int          $options option for json_encode
     *
     * @throws exception\SerializeException
     */
    public function toJson(mixed $data, int $options = 0): string;

    /**
     * Converts json to object.
     *
     * @param string        $jsonString
     * @param string|object $type
     *
     * @return mixed
     *
     * @throws exception\SerializeException
     * @throws JsonException
     */
    public function fromJson(string $jsonString, string|object $type): mixed;
}
