<?php
class Varien_Object_methods___toArrayTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $dataToPass
     * @param mixed $arrAttributes
     * @param string $expectedResult
     * @dataProvider __toArrayDataProvider
     */
    public function test__toArray($dataToPass, $arrAttributes, $expectedResult)
    {
        $object = new Varien_Object($dataToPass);
        $result = $object->__toArray($arrAttributes);
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public static function __toArrayDataProvider()
    {
        return array(
            'emptry array $arrAttributes' => array(
                array('a' => 'b', 1 => 2),
                array(),
                array('a' => 'b', 1 => 2),
            ),
            'extract string attribute' => array(
                array('a' => 'b', 1 => 2),
                array('a'),
                array('a' => 'b'),
            ),
            'extract int attribute' => array(
                array('a' => 'b', 1 => 2),
                array(1),
                array(1 => 2),
            ),
            'extract int attribute, sent as string' => array(
                array('a' => 'b', 1 => 2),
                array('1'),
                array(1 => 2),
            ),
            'extract converted false attribute' => array(
                array('a' => 'b', 0 => 1),
                array(false),
                array(0 => 1),
            ),
            'extract converted true attribute' => array(
                array('a' => 'b', 1 => 2),
                array(true),
                array(1 => 2),
            ),
            'extract converted null attribute' => array(
                array('a' => 'b', '' => 'c'),
                array(null),
                array('' => 'c'),
            ),
            'extract non-existing attributes' => array(
                array('a' => 'b', 1 => 2),
                array('c', 3),
                array('c' => null, 3 => null),
            ),
            'big array of attributes' => array (
                array('a' => 'b', 1 => 2, 'c' => 'd', 3 => 4, 'e' => 'f', 6 => 7),
                array(1, 2, 'a', 'e', '6'),
                array(1 => 2, 2 => null, 'a' => 'b', 'e' => 'f', 6 => 7),
            ),
        );
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function test__ToArrayWithNonScalarAttribute()
    {
        $attributes = array(new SplFileInfo('a'));
        $object = new Varien_Object(array('a' => 'b'));
        $result = $object->toArray($attributes);
    }

    /**
     * @param $argument
     * @dataProvider __toArrayWithNonArrayArgumentDataProvider
     */
    public function test__toArrayWithNonArrayArgument($argument)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'must be an array');
        $object = new Varien_Object();
        $object->__toArray($argument);
    }

    public static function __toArrayWithNonArrayArgumentDataProvider()
    {
        return array(
            array(null),
            array('string'),
        );
    }

    public function test__toArrayWithoutArgs()
    {
        $object = new Varien_Object(array('a' => 'b', 1 => 2));
        $this->assertEquals(array('a' => 'b', 1 => 2), $object->__toArray());
    }

    /**
     * Test running method without observing returned value - it must run without any internal php issues
     */
    public function test__toArrayNoReturnedValue()
    {
        $object = new Varien_Object();
        $object->__toArray();
    }

    public function test__toArrayNoReferencesInExtractedAttributes()
    {
        $a = 'a_value';
        $data = array('attr' => &$a);
        $object = new Varien_Object($data);

        $extracted = $object->__toArray(array('attr'));
        $this->assertEquals(array('attr' => 'a_value'), $extracted);

        $a = 'new_value';
        $this->assertEquals(array('attr' => 'new_value'), $data);
        $this->assertEquals(array('attr' => 'a_value'), $extracted);
    }
}
