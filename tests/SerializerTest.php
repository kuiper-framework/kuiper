<?php
namespace kuiper\serializer;

use kuiper\test\TestCase;
use kuiper\serializer\fixtures\Organization;
use kuiper\serializer\fixtures\Member;
use kuiper\serializer\fixtures\Company;
use kuiper\annotations\AnnotationReader;

/**
 * TestCase for Serializer
 */
class SerializerTest extends TestCase
{
    public function createSerializer()
    {
        return new Serializer(new AnnotationReader());
    }
    
    public function testDeserializeType()
    {
        $serializer = $this->createSerializer();
        $json = "{\"name\": \"Les-Tilleuls.coop\",\"members\":[{\"name\":\"K\\u00e9vin\"}]}";
        $org = $serializer->fromJson($json, Organization::class);
        // print_r($org);

        $this->assertTrue($org instanceof Organization);
        $this->assertEquals($org->getName(), "Les-Tilleuls.coop");
        $members = $org->getMembers();
        $this->assertTrue(is_array($members));
        $this->assertTrue($members[0] instanceof Member);
        $this->assertEquals($members[0]->getName(), "Kévin");
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
        $obj = new Company;
        $obj->name = 'Acme Inc.';
        $obj->address = '123 Main Street, Big City';
        $obj->employers = ['a', 'b'];
        $this->assertEquals($serializer->toJson($obj), '{"org_name":"Acme Inc.","org_address":"123 Main Street, Big City"}');
    }

    public function testSerializeType()
    {
        $serializer = $this->createSerializer();
        $org = new Organization;
        $org->setName('Les-Tilleuls.coop');
        $member = new Member;
        $member->setName('Kevin');
        $org->setMembers([$member]);
        
        $this->assertEquals(
            $serializer->toJson($org),
            '{"name":"Les-Tilleuls.coop","members":[{"name":"Kevin"}]}'
        );
    }
}
