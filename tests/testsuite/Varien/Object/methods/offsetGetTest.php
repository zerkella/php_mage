<?php
class Varien_Object_methods_offsetGetTest extends PHPUnit_Framework_TestCase
{

    /**
     * @param array $data
     * @param mixed $offset
     * @param bool $expected
     * @dataProvider offsetGetDataProvider
     */
    public function testOffsetGet(array $data, $offset, $expected)
    {
        $object = new Varien_Object($data);
        $actual = $object->offsetGet($offset);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public static function offsetGetDataProvider()
    {
        return array(
            'exists - string value, string offset' => array(
                array('a' => 'b'),
                'a',
                'b',
            ),
            'exists - int value, int offset' => array(
                array(5 => 6),
                5,
                6,
            ),
            'exists - string value, int offset' => array(
                array('5' => 'a'),
                5,
                'a',
            ),
            'exists - int value, string offset' => array(
                array(5 => 'a'),
                '5',
                'a',
            ),
            'exists - null value, string offset' => array(
                array(null => 'a'),
                '',
                'a',
            ),
            'exists - empty string value, null offset' => array(
                array('' => 'a'),
                null,
                'a',
            ),
            'not exists at all' => array(
                array(),
                'a',
                null,
            ),
            'not exists just this offset' => array(
                array('a' => 'b'),
                'b',
                null,
            ),
            'exists - null value' => array(
                array('a' => null),
                'a',
                null,
            ),
        );
    }

    /**
     * Test that a warning is correctly reported, when no parameters are passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testOffsetGetNoParams()
    {
        $object = new Varien_Object();
        $result = $object->offsetGet();
    }

    /**
     * Test that nothing bad happens, when the method is called without checking a return value
     *
     * @param array $data
     * @param mixed $offset
     * @dataProvider offsetGetWithoutReturnValueDataProvider
     */
    public function testOffsetGetWithoutReturnValue(array $data, $offset)
    {
        $object = new Varien_Object($data);
        $object->offsetGet($offset);
    }

    /**
     * @return array
     */
    public static function offsetGetWithoutReturnValueDataProvider()
    {
        return array(
            'no offset' => array(
                array(),
                'a',
            ),
            'offset is present' => array(
                array('a' => 'b'),
                'a',
            ),
        );
    }
}
