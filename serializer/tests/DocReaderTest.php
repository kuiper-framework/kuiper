<?php

declare(strict_types=1);

namespace kuiper\serializer;

use kuiper\serializer\fixtures\Company;
use kuiper\serializer\fixtures\query\Param;
use kuiper\serializer\fixtures\User;
use kuiper\serializer\fixtures\UserForm;
use PHPUnit\Framework\TestCase;

class DocReaderTest extends TestCase
{
    public function testTraitProperty()
    {
        $reader = new DocReader();
        $ret = $reader->getPropertyClass(new \ReflectionProperty(UserForm::class, 'param'));
        $this->assertEquals(Param::class, (string) $ret);
        $ret = $reader->getPropertyClass(new \ReflectionProperty(UserForm::class, 'user'));
        $this->assertEquals(User::class, (string) $ret);
        $ret = $reader->getPropertyClass(new \ReflectionProperty(UserForm::class, 'company'));
        $this->assertEquals(Company::class, (string) $ret);
    }
}
