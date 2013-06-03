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
            'key conversion to string' => array(
                array('a' => 'b', 1 => 2),
                new SplFileInfo('c'),
                'd',
                'd',
                array('a' => 'b', 1 => 2, 'c' => 'd'),
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
}
