<?php
class Varien_Object_methods_offsetExistsTest extends PHPUnit_Framework_TestCase
{

    /**
     * @param array $data
     * @param mixed $offset
     * @param bool $expected
     * @dataProvider offsetExistsDataProvider
     */
    public function testOffsetExists(array $data, $offset, $expected)
    {
        $object = new Varien_Object($data);
        $actual = $object->offsetExists($offset);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public static function offsetExistsDataProvider()
    {
        return array(
            'exists - string value, string offset' => array(
                array('a' => 'b'),
                'a',
                true,
            ),
            'exists - int value, int offset' => array(
                array(5 => 6),
                5,
                true,
            ),
            'exists - string value, int offset' => array(
                array('5' => 'a'),
                5,
                true,
            ),
            'exists - int value, string offset' => array(
                array(5 => 'a'),
                '5',
                true,
            ),
            'exists - null value, string offset' => array(
                array(null => 'a'),
                '',
                true,
            ),
            'exists - empty string value, null offset' => array(
                array('' => 'a'),
                null,
                true,
            ),
            'not exists at all' => array(
                array(),
                'a',
                false,
            ),
            'not exists just this offset' => array(
                array('a' => 'b'),
                'b',
                false,
            ),
            'not exists - null checked by isset' => array(
                array('a' => null),
                'a',
                false,
            ),
        );
    }

    /**
     * Test that a warning is correctly reported, when no parameters are passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testOffsetExistsNoParams()
    {
        $object = new Varien_Object();
        $exists = $object->offsetExists();
    }

    /**
     * Test that nothing bad happens, when the method is called without checking a return value
     *
     * @param array $data
     * @param mixed $offset
     * @dataProvider offsetExistsWithoutReturnValueDataProvider
     */
    public function testOffsetExistsWithoutReturnValue(array $data, $offset)
    {
        $object = new Varien_Object($data);
        $object->offsetExists($offset);
    }

    /**
     * @return array
     */
    public static function offsetExistsWithoutReturnValueDataProvider()
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
