<?php

namespace kuiper\serializer;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\DocReader;
use kuiper\helper\Enum;
use kuiper\serializer\fixtures\Collection;
use kuiper\serializer\fixtures\Company;
use kuiper\serializer\fixtures\Gender;
use kuiper\serializer\fixtures\Member;
use kuiper\serializer\fixtures\Organization;
use kuiper\serializer\fixtures\Store;
use kuiper\serializer\fixtures\User;
use kuiper\serializer\normalizer\DateTimeNormalizer;
use kuiper\serializer\normalizer\EnumNormalizer;

/**
 * TestCase for Serializer.
 */
class SerializerTest extends \PHPUnit_Framework_TestCase
{
    public function createSerializer()
    {
        return new Serializer(new AnnotationReader(), new DocReader(), [
            \DateTime::class => new DateTimeNormalizer(),
            Enum::class => new EnumNormalizer(),
        ]);
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
        $this->assertEquals($serializer->toJson($obj), '{"org_name":"Acme Inc.","org_address":"123 Main Street, Big City","@class":"kuiper\\\\serializer\\\\fixtures\\\\Company"}');
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
            '{"name":"Les-Tilleuls.coop","members":[{"name":"Kevin","@class":"kuiper\\\\serializer\\\\fixtures\\\\Member"}],"@class":"kuiper\\\\serializer\\\\fixtures\\\\Organization"}'
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
        $result = $serializer->denormalize([
            'values' => [
                ['name' => 'john'],
            ],
        ], fixtures\TypeOverrideByProperty::class);
        $this->assertInstanceOf(Member::class, $result->getValues()[0]);
    }

    public function testIntTypeAutoconvert()
    {
        $serializer = $this->createSerializer();
        $result = $serializer->denormalize(['id' => 1], fixtures\User::class);
        $this->assertInstanceOf(fixtures\User::class, $result);
        // var_export($result);
        $this->assertEquals(1, $result->getId());
    }

    public function testIntTypeAutoconvertNull()
    {
        $serializer = $this->createSerializer();
        $result = $serializer->denormalize(['id' => null], fixtures\User::class);
        $this->assertInstanceOf(fixtures\User::class, $result);
        // var_export($result);
        $this->assertNull($result->getId());
    }

    public function testIntTypeSerialize()
    {
        $serializer = $this->createSerializer();
        $user = new fixtures\User();
        $user->setId(1);
        $result = $serializer->normalize($user);
        // var_export($result);
        $this->assertArraySubset(['id' => 1], $result);
    }

    public function testDateTimeSerialize()
    {
        $serializer = $this->createSerializer();
        $user = $this->createUser();
        $str = $serializer->toJson($user);
        $obj = $serializer->fromJson($str, fixtures\User::class);
        // print_r($obj);
        $this->assertInstanceOf(\DateTime::class, $obj->getBirthday());
    }

    public function testDateTimeJsonSerialize()
    {
        $serializer = $this->createSerializer();
        $user = $this->createUser();
        $str = json_encode($user);
        // echo $str;
        /** @var fixtures\User $obj */
        $obj = $serializer->fromJson($str, fixtures\User::class);
         // print_r($obj);
        $this->assertInstanceOf(\DateTime::class, $obj->getBirthday());
        $this->assertEquals(Gender::MALE(), $obj->getGender());
    }

    public function testBooleanType()
    {
        $store = new Store();
        $store->setAdmin(true)
            ->setOpen(true);
        $serializer = $this->createSerializer();
        $str = $serializer->toJson($store);
        $this->assertEquals('{"open":true,"admin":true,"@class":"kuiper\\\\serializer\\\\fixtures\\\\Store"}', $str);
        $obj = $serializer->fromJson('{"open":true,"admin":true,"@class":"kuiper\\\\serializer\\\\fixtures\\\\Store"}', Store::class);
        // var_export($obj);
        $this->assertInstanceOf(Store::class, $obj);
        $this->assertTrue($obj->hasAdmin());
    }

    /**
     * @return fixtures\User
     */
    private function createUser(): fixtures\User
    {
        $user = new fixtures\User();
        $user->setId(1)
            ->setBirthday(new \DateTime())
            ->setGender(Gender::MALE());

        return $user;
    }

    public function testSerializeGenericClass()
    {
        $collection = new Collection();
        $collection->setTotal(10)
            ->setItems([$this->createUser()]);
        $serializer = $this->createSerializer();
        $json = $serializer->toJson($collection);
        // echo $json;

        /** @var Collection $obj */
        $obj = $serializer->fromJson($json, Collection::class);
        // var_export($obj);
        $this->assertInstanceOf(Collection::class, $obj);
        $this->assertTrue(is_array($obj->getItems()));
        $this->assertInstanceOf(User::class, $obj->getItems()[0]);
    }
}
