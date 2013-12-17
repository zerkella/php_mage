<?php
/**
 * Test implementation of ArrayAccess interface
 */
class Varien_Object_ArrayAccessTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->assertTrue(is_a('Varien_Object', 'ArrayAccess', true), 'Object does not implement ArrayAccess');
    }

    public function testRead()
    {
        $data = array('a' => 'b', 'c' => 'd');
        $object = new Varien_Object($data);

        $this->assertEquals('b', $object['a']);
    }

    public function testWrite()
    {
        $data = array('a' => 'b', 'c' => 'd');
        $object = new Varien_Object($data);

        $object['e'] = 'f';
        $this->assertEquals(array('a' => 'b', 'c' => 'd', 'e' => 'f'), $object->getData());

        $object['e'] = 'g';
        $this->assertEquals(array('a' => 'b', 'c' => 'd', 'e' => 'g'), $object->getData());
    }

    public function testIsset()
    {
        $data = array('a' => 'b', 'c' => 'd');
        $object = new Varien_Object($data);

        $this->assertTrue(isset($object['a']));
        $this->assertFalse(isset($object['e']));
    }

    public function testUnset()
    {
        $data = array('a' => 'b', 'c' => 'd');
        $object = new Varien_Object($data);

        unset($object['a']);
        $this->assertEquals(array('c' => 'd'), $object->getData());
    }
}
