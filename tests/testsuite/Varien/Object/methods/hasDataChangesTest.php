<?php
class Varien_Object_methods_hasDataChangesTest extends PHPUnit_Framework_TestCase
{
    public function testHasDataChanges()
    {
        $object = new Varien_Object();
        $this->assertFalse($object->hasDataChanges(), 'Object must be non-changed by default');
        $object->setData(array());
        $this->assertTrue($object->hasDataChanges(), 'Object must has changes after setting data to it');
    }
}
