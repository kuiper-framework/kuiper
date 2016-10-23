<?php

namespace kuiper\serializer;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReader;
use kuiper\serializer\fixtures\Company;
use kuiper\serializer\fixtures\Member;
use kuiper\serializer\fixtures\Organization;

/**
 * TestCase for Serializer.
 */
class SerializerTest extends \PHPUnit_Framework_TestCase
{
    public function createSerializer()
    {
        return new Serializer(new AnnotationReader(), new DocReader());
    }

    public function testDeserializeType()
    {
        $serializer = $this->createSerializer();
        $json = '{"name": "Les-Tilleuls.coop","members":[{"name":"K\\u00e9vin"}]}';
        $org = $serializer->fromJson($json, Organization::class);
        // print_r($org);

        $this->assertTrue($org instanceof Organization);
        $this->assertEquals($org->getName(), 'Les-Tilleuls.coop');
        $members = $org->getMembers();
        $this->assertTrue(is_array($members));
        $this->assertTrue($members[0] instanceof Member);
        $this->assertEquals($members[0]->getName(), 'Kévin');
    }

    public function testDeserializeName()
    {
        $serializer = $this->createSerializer();
        $json = '{"org_name": "Acme Inc.", "org_address": "123 Main Street, Big City", "employers": "abc"}';
        $obj = $serializer->fromJson($json, Company::class);
        // print_r($obj);
        $this->assertEquals($obj->name, 'Acme Inc.');
        $this->assertNull($obj->employers);
    }

    public function testSerializeName()
    {
        $serializer = $this->createSerializer();
        $obj = new Company();
        $obj->name = 'Acme Inc.';
        $obj->address = '123 Main Street, Big City';
        $obj->employers = ['a', 'b'];
        $this->assertEquals($serializer->toJson($obj), '{"org_name":"Acme Inc.","org_address":"123 Main Street, Big City"}');
    }

    public function testSerializeType()
    {
        $serializer = $this->createSerializer();
        $org = new Organization();
        $org->setName('Les-Tilleuls.coop');
        $member = new Member();
        $member->setName('Kevin');
        $org->setMembers([$member]);

        $this->assertEquals(
            $serializer->toJson($org),
            '{"name":"Les-Tilleuls.coop","members":[{"name":"Kevin"}]}'
        );
    }

    public function testUnserializeArray()
    {
        $serializer = $this->createSerializer();
        $data = $serializer->fromJson('[{"name":"Les-Tilleuls.coop","members":[{"name":"Kevin"}]}]', Organization::class.'[]');
        $this->assertTrue(is_array($data));
        $this->assertInstanceOf(Organization::class, $data[0]);
        $this->assertEquals('Les-Tilleuls.coop', $data[0]->getName());
    }

    public function testTypeOverriderByProperty()
    {
        $serializer = $this->createSerializer();
        $result = $serializer->fromArray([
            'values' => [
                ['name' => 'john'],
            ],
        ], fixtures\TypeOverrideByProperty::class);
        $this->assertInstanceOf(Member::class, $result->getValues()[0]);
    }

    public function testIntTypeAutoconvert()
    {
        $serializer = $this->createSerializer();
        $result = $serializer->fromArray(['id' => 1], fixtures\User::class);
        $this->assertInstanceOf(fixtures\User::class, $result);
        // var_export($result);
        $this->assertEquals(1, $result->getId());
    }

    public function testIntTypeAutoconvertNull()
    {
        $serializer = $this->createSerializer();
        $result = $serializer->fromArray(['id' => null], fixtures\User::class);
        $this->assertInstanceOf(fixtures\User::class, $result);
        // var_export($result);
        $this->assertNull($result->getId());
    }

    public function testIntTypeSerialize()
    {
        $serializer = $this->createSerializer();
        $user = new fixtures\User();
        $user->setId(1);
        $result = $serializer->toArray($user);
        // var_export($result);
        $this->assertEquals(['id' => 1], $result);
    }
}