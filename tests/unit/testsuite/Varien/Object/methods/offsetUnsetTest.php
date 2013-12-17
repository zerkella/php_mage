<?php
class Varien_Object_methods_offsetUnsetTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param mixed $offset
     * @param bool $expectedData
     * @dataProvider offsetUnsetDataProvider
     */
    public function testOffsetUnset(array $data, $offset, $expectedData)
    {
        $object = new Varien_Object($data);
        $object->offsetUnset($offset);
        $actualData = $object->getData();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public static function offsetUnsetDataProvider()
    {
        return array(
            'normal unset' => array(
                array('a' => 'b', 'c' => 'd'),
                'a',
                array('c' => 'd'),
            ),
            'normal unset int' => array(
                array(5 => 6, 7 => 8),
                5,
                array(7 => 8),
            ),
            'no value to offset' => array(
                array('a' => 'b', 'c' => 'd'),
                'e',
                array('a' => 'b', 'c' => 'd'),
            ),
            'int offset, but remove string offset' => array(
                array(5 => 'a', 'c' => 'd'),
                '5',
                array('c' => 'd'),
            ),
            'string offset, but remove int offset' => array(
                array('5' => 'a', 'c' => 'd'),
                5,
                array('c' => 'd'),
            ),
            'int offset, but remove true offset' => array(
                array(1 => 'a', 'c' => 'd'),
                true,
                array('c' => 'd'),
            ),
            'int offset, but remove false offset' => array(
                array(0 => 'a', 'c' => 'd'),
                false,
                array('c' => 'd'),
            ),
            'unset empty string offset' => array(
                array('' => 'a', 'c' => 'd'),
                '',
                array('c' => 'd'),
            ),
            'empty string offset, but remove null offset' => array(
                array('' => 'a', 'c' => 'd'),
                null,
                array('c' => 'd'),
            ),
        );
    }

    /**
     * Test that a warning is correctly reported, when no parameters are passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testOffsetUnsetNoParams()
    {
        $object = new Varien_Object();
        $object->offsetUnset();
    }
}
