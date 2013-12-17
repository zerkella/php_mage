<?php
class Varien_Object_methods__prepareSyncFieldsMapTest extends PHPUnit_Framework_TestCase
{
    public function testPrepareSyncFieldsMap()
    {
        $reflection = new ReflectionClass('Varien_Object');
        $refMethod = $reflection->getMethod('_prepareSyncFieldsMap');
        $refMethod->setAccessible(true);

        $object = new Varien_Object();
        $result = $refMethod->invoke($object);
        $this->assertSame($object, $result);
    }
}
