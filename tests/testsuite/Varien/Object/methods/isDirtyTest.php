<?php
class Varien_Object_methods_isDirtyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $dirty
     * @param array $params
     * @param mixed $expected
     * @dataProvider isDirtyDataProvider
     */
    public function testIsDirty(array $dirty, array $params, $expected)
    {
        $property = new ReflectionProperty('Varien_Object', '_dirty');
        $property->setAccessible(true);

        $object = new Varien_Object();
        $property->setValue($object, $dirty);

        $actual = call_user_func_array(array($object, 'isDirty'), $params);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public static function isDirtyDataProvider()
    {
        return array(
            '_dirty is empty array, $field = null does not matter' => array(
                array(),
                array(null),
                false,
            ),
            '_dirty is empty array, string $field does not matter' => array(
                array(),
                array('a'),
                false,
            ),
            '_dirty is empty array, $field = string does not matter' => array(
                array(),
                array('a'),
                false,
            ),
            '_dirty is not empty array, $field = null always returns true' => array(
                array('a' => 'b'),
                array(null),
                true,
            ),
            '_dirty is not empty array, $field is null by default' => array(
                array('a' => 'b'),
                array(),
                true,
            ),
            'non-existing field' => array(
                array('a' => 'b'),
                array('c'),
                false,
            ),
            'existing field' => array(
                array('a' => 'b'),
                array('a'),
                true,
            ),
            'existing field for null value returns false' => array(
                array('a' => null),
                array('a'),
                false,
            ),
            'string-int field for int value' => array(
                array(5 => 'a'),
                array('5'),
                true,
            ),
            'string-int field for string-int value' => array(
                array('5' => 'a'),
                array('5'),
                true,
            ),
            'int field for int value' => array(
                array(5 => 'a'),
                array(5),
                true,
            ),
            'object field ' => array(
                array('a' => 'b'),
                array(new SplFileInfo('a')),
                true,
            ),
        );
    }

    public function testIsDirtyWithoutReturnValue()
    {
        $object = new Varien_Object();
        $object->isDirty();
    }
}
