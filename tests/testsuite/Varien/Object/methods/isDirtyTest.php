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
        /**
         * There is a bug in Magento 1 - _dirty is absent, so flagDirty is not usable. Fix the bug with custom
         * descendant. Not so much sense, though, because 'dirty' functionality is not used anymore.
         */
        $refClass = new ReflectionClass('Varien_Object');
        $classUsed = $refClass->hasProperty('_dirty') ?
            'Varien_Object'
            : 'Zerkella_PhpMage_Varien_Object_Descendant_BugFix_DirtyProperty';

        $property = new ReflectionProperty($classUsed, '_dirty');
        $property->setAccessible(true);

        $object = new $classUsed();
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
        );
    }

    /**
     * Test, that nothing bad happens, if return value is not checked at all
     */
    public function testIsDirtyWithoutReturnValue()
    {
        $object = new Varien_Object();
        $object->isDirty();
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testIsDirtyWithNonScalarParam()
    {
        $refClass = new ReflectionClass('Varien_Object');
        $classUsed = $refClass->hasProperty('_dirty') ?
            'Varien_Object'
            : 'Zerkella_PhpMage_Varien_Object_Descendant_BugFix_DirtyProperty';

        $property = new ReflectionProperty($classUsed, '_dirty');
        $property->setAccessible(true);

        $objParam = new SplFileInfo('a');
        $object = new $classUsed(array('a' => 'b'));
        $property->setValue($object, array('a' => 'b'));

        $result = $object->isDirty($objParam);
    }
}
