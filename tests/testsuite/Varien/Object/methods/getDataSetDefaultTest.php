<?php
class Varien_Object_methods_getDataSetDefaultTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $dataToPass
     * @param mixed $key
     * @param mixed $default
     * @param mixed $expectedResult
     * @param array $expectedData
     * @dataProvider getDataSetDefaultDataProvider
     */
    public function testGetDataSetDefault($dataToPass, $key, $default, $expectedResult, $expectedData)
    {
        $object = new Varien_Object($dataToPass);
        $result = $object->getDataSetDefault($key, $default);
        $this->assertSame($expectedResult, $result);
        $this->assertSame($expectedData, $object->getData());
    }

    public static function getDataSetDefaultDataProvider()
    {
        return array(
            'string key of existing data' => array(
                array('a' => 'b', 1 => 2),
                'a',
                'c',
                'b',
                array('a' => 'b', 1 => 2),
            ),
            'int key of existing data' => array(
                array('a' => 'b', 1 => 2),
                1,
                3,
                2,
                array('a' => 'b', 1 => 2),
            ),
            'string key of non-existing data' => array(
                array('a' => 'b', 1 => 2),
                'c',
                'd',
                'd',
                array('a' => 'b', 1 => 2, 'c' => 'd'),
            ),
            'int key of non-existing data' => array(
                array('a' => 'b', 1 => 2),
                3,
                4,
                4,
                array('a' => 'b', 1 => 2, 3 => 4),
            ),
            'string key of null value' => array(
                array('a' => null, 1 => 2),
                'a',
                'b',
                'b',
                array('a' => 'b', 1 => 2),
            ),
            'int key of null value' => array(
                array('a' => 'b', 1 => null),
                1,
                2,
                2,
                array('a' => 'b', 1 => 2),
            ),
            'string key, containing int' => array(
                array('a' => 'b', 1 => 2),
                '1',
                5,
                2,
                array('a' => 'b', 1 => 2),
            ),
            'string key, containing int, non-existing value' => array(
                array('a' => 'b', 1 => 2),
                '3',
                4,
                4,
                array('a' => 'b', 1 => 2, 3 => 4),
            ),
            'key (null) conversion to string' => array(
                array('a' => 'b', 1 => 2),
                null,
                'd',
                'd',
                array('a' => 'b', 1 => 2, '' => 'd'),
            ),
            'key (false) conversion to string' => array(
                array('a' => 'b', 1 => 2),
                false,
                'd',
                'd',
                array('a' => 'b', 1 => 2, 0 => 'd'),
            ),
            'key (true) conversion to string' => array(
                array('a' => 'b', 1 => 2),
                true,
                'd',
                2,
                array('a' => 'b', 1 => 2),
            ),
        );
    }

    public function testGetDataSetDefaultNoDataLink()
    {
        $data = array('a' => 'b', 1 => 2);
        $object = new Varien_Object($data);

        $object->getDataSetDefault('c', 'd');
        $this->assertSame(array('a' => 'b', 1 => 2, 'c' => 'd'), $object->getData());
        $this->assertSame(array('a' => 'b', 1 => 2), $data);
    }

    /**
     * Test that a warning is correctly reported, when parameters are not passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testGetDataSetDefaultNoParams()
    {
        $object = new Varien_Object();
        $result = $object->getDataSetDefault();
    }

    /**
     * Test that a warning is correctly reported, when one parameter is not passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testGetDataSetDefaultNotEnoughParams()
    {
        $object = new Varien_Object();
        $result = $object->getDataSetDefault('a');
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testtGetDataSetDefaultNonScalarAttribute()
    {
        $key = new SplFileInfo('a');
        $object = new Varien_Object(array('a' => 'b'));
        $result = $object->getDataSetDefault($key, 'c');
    }
}
