<?php
class Varien_Object_methods__prepareArrayTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ReflectionMethod
     */
    protected $_method;

    public function setUp()
    {
        $this->_method = new ReflectionMethod('Varien_Object', '_prepareArray');
        $this->_method->setAccessible(true);
    }

    /**
     * @param mixed $arr
     * @param array $elements
     * @param array $expected
     * @dataProvider _prepareArrayDataProvider
     */
    public function test_prepareArray($arr, $elements, $expected)
    {
        $object = new Varien_Object();
        // invokeArgs() is used instead of invoke(), because the latter doesn't link back pass-by-reference correctly
        $result = $this->_method->invokeArgs($object, array('arr' => &$arr, 'elements' => $elements));

        $this->assertEquals($expected, $result);
        $this->assertEquals($result, $arr);
    }

    /**
     * @return array
     */
    public static function _prepareArrayDataProvider()
    {
        return array(
            'non-empty array passed' => array(
                array('a' => 'b'),
                array('c'),
                array('a' => 'b', 'c' => null),
            ),
            'empty array passed' => array(
                array('a' => 'b'),
                array(),
                array('a' => 'b'),
            ),
            'elements have the existing key' => array(
                array('a' => 'b'),
                array('a'),
                array('a' => 'b'),
            ),
            'elements have the existing key with null value' => array(
                array('a' => null),
                array('a'),
                array('a' => null),
            ),
            'int key' => array(
                array(),
                array(1),
                array(1 => null),
            ),
            'int key of string type' => array(
                array(),
                array('1'),
                array(1 => null),
            ),
            'existing int key' => array(
                array(1 => 'a'),
                array(1),
                array(1 => 'a'),
            ),
            'existing int key of string type' => array(
                array(1 => 'a'),
                array('1'),
                array(1 => 'a'),
            ),
            'existing int key for string int key' => array(
                array('1' => 'a'),
                array(1),
                array(1 => 'a'),
            ),
            'several keys to add' => array(
                array('a' => 'b'),
                array(1, 'a', 'c'),
                array('a' => 'b', 1 => null, 'c' => null),
            ),
            'non-existin variable (simulate call without reflection)' => array(
                null,
                array('a'),
                array('a' => null),
            ),
        );
    }

    public function test_prepareArrayWithoutPassingElements()
    {
        $object = new Varien_Object();

        $arr = array('a', 'b');
        $result = $this->_method->invokeArgs($object, array('arr' => &$arr));
        $this->assertEquals(array('a', 'b'), $arr);
        $this->assertEquals(array('a', 'b'), $result);
    }

    public function test_prepareArrayWithoutPassingElementsAndTestingResult()
    {
        $object = new Varien_Object();

        $arr = array('a', 'b');
        $this->_method->invokeArgs($object, array('arr' => &$arr));
        $this->assertEquals(array('a', 'b'), $arr);
    }

    /**
     * @param mixed $elements
     * @dataProvider _prepareArrayWithNonArrayArgumentDataProvider
     */
    public function test_prepareArrayWithNonArrayArgument($elements)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'array');
        $object = new Varien_Object();

        $arr = array();
        $this->_method->invokeArgs($object, array('arr' => &$arr, 'elements' => $elements));
    }

    /**
     * @return array
     */
    public static function _prepareArrayWithNonArrayArgumentDataProvider()
    {
        return array(
            array(null),
            array('string'),
        );
    }
}
