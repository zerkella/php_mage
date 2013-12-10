<?php
class Varien_Object_methods_flagDirtyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param array $dirty
     * @param array $params
     * @param array $expectedDirty
     * @dataProvider flagDirtyDataProvider
     */
    public function testFlagDirty(array $data, array $dirty, array $params, array $expectedDirty)
    {
        /**
         * There is a bug in Magento 1 - _dirty is absent, so flagDirty is not usable. Fix the bug with custom
         * descendant. Not so much sense, though, because 'dirty' functionality is not used anymore.
         */
        $refClass = new ReflectionClass('Varien_Object');
        $classUsed = $refClass->hasProperty('_dirty') ?
            'Varien_Object'
            : 'Zerkella_PhpMage_Varien_Object_Descendant_BugFix_DirtyProperty';

        // Proceed with testing
        $property = new ReflectionProperty($classUsed, '_dirty');
        $property->setAccessible(true);

        $object = new $classUsed($data);
        $property->setValue($object, $dirty);

        $result = call_user_func_array(array($object, 'flagDirty'), $params);
        $this->assertSame($object, $result);

        $newDirty = $property->getValue($object);
        $this->assertSame($expectedDirty, $newDirty);
    }

    /**
     * @return array
     */
    public static function flagDirtyDataProvider()
    {
        return array(
            'null $field, copy _data with $flag = true explicit' => array(
                array('a' => 'b', 'c' => 'd'),
                array('e' => false),
                array(null, true),
                array('e' => false, 'a' => true, 'c' => true),
            ),
            'null $field, copy _data with $flag = true implicit' => array(
                array('a' => 'b', 'c' => 'd'),
                array('e' => false),
                array(null),
                array('e' => false, 'a' => true, 'c' => true),
            ),
            'null $field, copy _data with int field $flag = true' => array(
                array('5' => 6),
                array('e' => false),
                array(null),
                array('e' => false, 5 => true),
            ),
            'null $field, unset non-existing _data with $flag = false' => array(
                array('a' => 'b', 'c' => 'd'),
                array('e' => true),
                array(null, false),
                array('e' => true),
            ),
            'null $field, unset existing _data with $flag = false' => array(
                array('a' => 'b', 'c' => 'd'),
                array('a' => true, 'e' => true),
                array(null, false),
                array('e' => true),
            ),
            'null $field, unset existing _data with int field $flag = false' => array(
                array('a' => 'b', '5' => 6),
                array('e' => true, 5 => true),
                array(null, false),
                array('e' => true),
            ),
            'null $field, everything is empty' => array(
                array(),
                array(),
                array(null),
                array(),
            ),
            'setting true for an index' => array(
                array(),
                array('e' => true, 'f' => false),
                array('g', true),
                array('e' => true, 'f' => false, 'g' => true),
            ),
            'setting true for an index, _data doesn\'t interfere' => array(
                array('a' => true),
                array('e' => true, 'f' => false),
                array('g', true),
                array('e' => true, 'f' => false, 'g' => true),
            ),
            'setting value for an index must convert int $flag to bool' => array(
                array(),
                array('e' => true, 'f' => false),
                array('g', 1),
                array('e' => true, 'f' => false,  'g' => true),
            ),
            'setting value for an index must convert string $flag to bool' => array(
                array(),
                array('e' => true, 'f' => false),
                array('g', 'abrakadabra'),
                array('e' => true, 'f' => false,  'g' => true),
            ),
            'setting false for an index, _data doesn\'t interfere' => array(
                array('a' => true),
                array('e' => true, 'f' => false),
                array('f', false),
                array('e' => true),
            ),
            'setting int $flag converted to \'false\' for an index' => array(
                array(),
                array('e' => true, 'f' => false),
                array('f', 0),
                array('e' => true),
            ),
            'setting string $flag converted to \'false\' for an index' => array(
                array(),
                array('e' => true, 'f' => false),
                array('f', ''),
                array('e' => true),
            ),
            'setting int $field' => array(
                array(),
                array('a' => true),
                array('5', true),
                array('a' => true, 5 => true),
            ),
            'unsetting int $field' => array(
                array(),
                array('a' => true, 5 => true),
                array('5', false),
                array('a' => true),
            ),
        );
    }

    /**
     * Test that nothing bad happens, when the method is called without checking a return value
     */
    public function testFlagDirtyWithoutReturnValue()
    {
        $object = new Varien_Object();
        $object->flagDirty(null);
    }

    /**
     * Test that a warning is correctly reported, when no parameters are passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testFlagDirtyNoParams()
    {
        $object = new Varien_Object();
        $object->flagDirty();
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     */
    public function testFlagDirtySubException()
    {
        $object = $this->getMock('Varien_Object', array('getData'));
        $object->expects($this->once())
            ->method('getData')
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->flagDirty(null);
    }
}
