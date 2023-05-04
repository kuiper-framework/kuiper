<?php

declare(strict_types=1);

namespace kuiper\serializer\normalizer;

use kuiper\serializer\fixtures\EnumGender;
use kuiper\serializer\fixtures\Gender;
use kuiper\serializer\fixtures\IntGender;
use kuiper\serializer\fixtures\IntGenderEnum;
use PHPUnit\Framework\TestCase;

class EnumNormalizerTest extends TestCase
{
    public function testDenormalizePhpEnum()
    {
        $normalizer = new PhpEnumNormalizer();
        $ret = $normalizer->denormalize(1, IntGenderEnum::class);
        $this->assertEquals(IntGenderEnum::MALE, $ret);
        $ret = $normalizer->denormalize('MALE', IntGenderEnum::class);
        $this->assertEquals(IntGenderEnum::MALE, $ret);

        $ret = $normalizer->denormalize('male', EnumGender::class);
        $this->assertEquals(EnumGender::MALE, $ret);
        $ret = $normalizer->denormalize('MALE', EnumGender::class);
        $this->assertEquals(EnumGender::MALE, $ret);
    }

    public function testDenormalizeEnum()
    {
        $normalizer = new EnumNormalizer();
        $ret = $normalizer->denormalize(1, IntGender::class);
        $this->assertEquals(IntGender::MALE(), $ret);
        $ret = $normalizer->denormalize('MALE', IntGender::class);
        $this->assertEquals(IntGender::MALE(), $ret);

        $ret = $normalizer->denormalize('male', Gender::class);
        $this->assertEquals(Gender::MALE(), $ret);
        $ret = $normalizer->denormalize('MALE', Gender::class);
        $this->assertEquals(Gender::MALE(), $ret);
    }
}
