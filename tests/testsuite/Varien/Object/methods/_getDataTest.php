<?php
class Varien_Object_methods__getDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param mixed $key
     * @param mixed $expected
     * @dataProvider getDataDataProvider
     */
    public function test_getData($data, $key, $expected)
    {
        $reflection = new ReflectionClass('Varien_Object');
        $refMethod = $reflection->getMethod('_getData');
        $refMethod->setAccessible(true);

        $object = new Varien_Object($data);
        $actual = $refMethod->invoke($object, $key);
        $this->assertSame($actual, $expected);
    }

    /**
     * @return array
     */
    public static function getDataDataProvider()
    {
        $objectKey = new SplFileInfo('key');
        $objectValue = new StdClass;
        return array(
            'string key' => array(
                array('a' => 'b'),
                'a',
                'b'
            ),
            'int key' => array(
                array(1 => 'b'),
                1,
                'b'
            ),
            'string key with integer value' => array(
                array('1' => 'b'),
                1,
                'b'
            ),
            'object key' => array(
                array('key' => 'b'),
                $objectKey,
                'b'
            ),
            'object value' => array(
                array('a' => $objectValue),
                'a',
                $objectValue
            ),
            'non-existing key' => array(
                array('a' => 'b'),
                'c',
                null
            ),
        );
    }
}
