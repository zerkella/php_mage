<?php
class Varien_Object_methods__camelizeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $name
     * @param string $expected
     * @dataProvider _camelizeDataProvider
     */
    public function test_camelize($name, $expected)
    {
        $object = new Varien_Object();
        $method = new ReflectionMethod('Varien_Object', '_camelize');
        $method->setAccessible(true);
        $result = $method->invoke($object, $name);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public static function _camelizeDataProvider()
    {
        return array(
            array('string', 'String'),
            array('an_underscored_string', 'AnUnderscoredString'),
            array('_underscore_first', 'UnderscoreFirst'),
            array('ACamelizedString', 'ACamelizedString'),
            array('camelizedStringNoCapFirst', 'CamelizedStringNoCapFirst'),
            array('string_with2numbers_in44_it', 'StringWith2numbersIn44It'),
            array('number_after_underscore_44', 'NumberAfterUnderscore44'),
            array(1, '1'),
            array(true, '1'),
            array(null, ''),
            array(new SplFileInfo('underscored_object'), 'UnderscoredObject'),
        );
    }
}
