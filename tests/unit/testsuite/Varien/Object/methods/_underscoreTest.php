<?php
class Varien_Object_methods__underscoreTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $name
     * @param string $expected
     * @dataProvider _underscoreDataProvider
     */
    public function test_underscore($name, $expected)
    {
        $object = new Varien_Object();
        $method = new ReflectionMethod('Varien_Object', '_underscore');
        $method->setAccessible(true);
        $result = $method->invoke($object, $name);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public static function _underscoreDataProvider()
    {
        return array(
            array('string', 'string'),
            array('an_underscored_string', 'an_underscored_string'),
            array('ACamelizedString', 'a_camelized_string'),
            array('camelizedStringNoCapFirst', 'camelized_string_no_cap_first'),
            array('stringWith2numbersIn44It', 'string_with2numbers_in44_it'),
            array(1, '1'),
            array(true, '1'),
            array(null, ''),
            array('CamelizedString', 'camelized_string'),
        );
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function test_unserscoreWithNonScalarAttribute()
    {
        $object = new Varien_Object();
        $method = new ReflectionMethod('Varien_Object', '_underscore');
        $method->setAccessible(true);

        $name = new SplFileInfo('ObjectName');
        $result = $method->invoke($object, $name);
    }
}
