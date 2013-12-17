<?php
class Varien_Object_methods_isEmptyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $data
     * @dataProvider isEmptyDataProvider
     */
    public function testIsEmpty($data)
    {
        $object = new Varien_Object();
        $property = new ReflectionProperty('Varien_Object', '_data');
        $property->setAccessible(true);
        $property->setValue($object, $data);
        $result = $object->isEmpty();
        $this->assertEquals(empty($data), $result);
    }

    /**
     * @return array
     */
    public static function isEmptyDataProvider()
    {
        return array(
            array(array()),
            array(array(0)),
            array(0),
            array(1),
            array(false),
            array(true),
            array(0.0),
            array(''),
            array('0'),
            array('0.0'),
            array(new stdClass()),
            array(null),
        );
    }
}
