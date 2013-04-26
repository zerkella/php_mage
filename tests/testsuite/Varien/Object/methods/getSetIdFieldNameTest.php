<?php
class Varien_Object_methods_getSetIdFieldName extends PHPUnit_Framework_TestCase
{
    public function testGetIdFieldName()
    {
        $object = new Varien_Object;
        $this->assertNull($object->getIdFieldName());
    }

    public function testGetSetIdFieldName()
    {
        $object = new Varien_Object();
        $result = $object->setIdFieldName('id_field');
        $this->assertSame($object, $result);
        $this->assertEquals('id_field', $object->getIdFieldName());
    }

    public function testSetIdFieldNameWithRefCountValue()
    {
        $object = new Varien_Object();
        $value = 'id_field';
        $refCount = $value;
        $object->setIdFieldName($refCount);

        $value = 'new_value';
        $this->assertEquals('id_field', $object->getIdFieldName());
        $refCount = 'new_value_refCount';
        $this->assertEquals('id_field', $object->getIdFieldName());
    }

    public function testSetIdFieldNameWithRefValue()
    {
        $object = new Varien_Object();
        $value = 'id_field';
        $refCount = &$value;
        $object->setIdFieldName($refCount);

        $value = 'new_value';
        $this->assertEquals('id_field', $object->getIdFieldName());
        $refCount = 'new_value_refCount';
        $this->assertEquals('id_field', $object->getIdFieldName());
    }

    public function testSetIdFieldNameWithObject()
    {
        $object = new Varien_Object();
        $value = new StdClass;
        $object->setIdFieldName($value);
        $this->assertSame($value, $object->getIdFieldName());
    }
}
