<?php
class Varien_Object_methods_dataHasChangedForTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $origData
     * @param array $data
     * @param mixed $field
     * @param mixed $expected
     * @dataProvider dataHasChangedForDataProvider
     */
    public function testDataHasChangedFor(array $origData, array $data, $field, $expected)
    {
        $object = new Varien_Object($origData);
        $object->setOrigData();
        $object->setData($data);
        $actual = $object->dataHasChangedFor($field);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public static function dataHasChangedForDataProvider()
    {
        return array(
            'not changed' => array(
                array('a' => 12),
                array('a' => 12),
                'a',
                false,
            ),
            'changed' => array(
                array('a' => 12),
                array('a' => 13),
                'a',
                true,
            ),
            'not changed, int vs string' => array(
                array('a' => 12),
                array('a' => '12'),
                'a',
                false,
            ),
            'not changed, empty string vs null' => array(
                array('a' => false),
                array('a' => null),
                'a',
                false,
            ),
            'not changed, non-existing key' => array(
                array(),
                array(),
                'a',
                false,
            ),
            'changed, whole data comparison' => array(
                array('a' => 12, 'b' => 22),
                array('a' => 12, 'b' => 23),
                null,
                true,
            ),
        );
    }

    /**
     * Test that nothing bad happens, when the method is called without checking a return value
     */
    public function testDataHasChangedForWithoutReturnValue()
    {
        $object = new Varien_Object();
        $object->dataHasChangedFor('a');
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     */
    public function testDataHasChangedForGetDataException()
    {
        $object = $this->getMock('Varien_Object', array('getData'));
        $object->expects($this->once())
            ->method('getData')
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->dataHasChangedFor('a');
    }

    /**
     * Test that a warning is correctly reported, when no parameters are passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testDataHasChangedForNoParams()
    {
        $object = new Varien_Object();
        $result = $object->dataHasChangedFor();
    }
}
