<?php
class Varien_Object_methods_hasDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $dataToPass
     * @param mixed $key
     * @param string $expectedResult
     * @dataProvider hasDataDataProvider
     */
    public function testHasData($dataToPass, $key, $expectedResult)
    {
        $object = new Varien_Object($dataToPass);
        $result = $object->hasData($key);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public static function hasDataDataProvider()
    {
        return array(
            'null key' => array(
                array('a' => 'b', 1 => 2),
                null,
                true,
            ),
            'null key with empty data' => array(
                array(),
                null,
                false,
            ),
            'emptry string' => array(
                array('a' => 'b', 1 => 2),
                '',
                true,
            ),
            'emptry string with empty data' => array(
                array(),
                '',
                false,
            ),
            'integer' => array(
                array('a' => 'b', 1 => 2),
                1,
                true,
            ),
            'integer with empty data' => array(
                array(),
                1,
                false,
            ),
            'object' => array(
                array('a' => 'b', 1 => 2),
                new SplFileInfo('a'),
                true,
            ),
            'object with empty data' => array(
                array(),
                new SplFileInfo('a'),
                false,
            ),
            'string - existing key' => array(
                array('a' => 'b', 1 => 2),
                'a',
                true,
            ),
            'string - existing int key' => array(
                array('a' => 'b', 1 => 2),
                '1',
                true,
            ),
            'string - existing key with null value' => array(
                array('a' => null, 1 => 2),
                'a',
                true,
            ),
            'string - non-existing key' => array(
                array('a' => 'b', 1 => 2),
                'c',
                false,
            ),
        );
    }

    public function testHasDataWithoutArgs()
    {
        $object = new Varien_Object(array('a' => 'b', 1 => 2));
        $this->assertTrue($object->hasData());

        $object = new Varien_Object();
        $this->assertFalse($object->hasData());
    }

    /**
     * Test running method without observing returned value - it must run without any internal php issues
     */
    public function testHasDataNoReturnedValue()
    {
        $object = new Varien_Object();
        $object->hasData();
    }
}
