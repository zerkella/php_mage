<?php
class Varien_Object_methods_serializeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param array $arguments
     * @param string $expected
     * @dataProvider serializeDataProvider
     */
    public function testSerialize(array $data, array $arguments, $expected)
    {
        $object = new Varien_Object($data);
        $result = call_user_func_array(array($object, 'serialize'), $arguments);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public static function serializeDataProvider()
    {
        return array(
            'all default params' => array(
                array('a' => 'b', 3 => 4),
                array(),
                'a="b" 3="4"',
            ),
            'nothing to serialize - empty data' => array(
                array(),
                array(array('a')),
                '',
            ),
            'negative key and value' => array(
                array(-17 => -54),
                array(),
                '-17="-54"',
            ),
            'null as attribute list' => array(
                array('a' => 'b'),
                array(null),
                'a="b"',
            ),
            'false as attribute list' => array(
                array('a' => 'b'),
                array(false),
                'a="b"',
            ),
            'empty array as attribute list' => array(
                array('a' => 'b'),
                array(array()),
                'a="b"',
            ),
            'empty string as attribute list' => array(
                array('a' => 'b'),
                array(''),
                'a="b"',
            ),
            'zero as attribute list' => array(
                array('a' => 'b'),
                array(0),
                'a="b"',
            ),
            'zero string as attribute list' => array(
                array('a' => 'b'),
                array('0'),
                'a="b"',
            ),
            'filtered attributes' => array(
                array('a' => 'b', 'c' => 'd'),
                array(array('c')),
                'c="d"',
            ),
            'filtered attributes, empty string as result' => array(
                array('a' => 'b', 'c' => 'd'),
                array(array('e')),
                '',
            ),
            'filtered attribute with int value' => array(
                array('a', 'b', 'c'),
                array(array(1)),
                '1="b"',
            ),
            'filtered attribute with string int value' => array(
                array('a', 'b', 'c'),
                array(array('1')),
                '1="b"',
            ),
            'filtered attribute with converted value' => array(
                array('a' => 'b', 'c' => 'd'),
                array(array(new SplFileInfo('c'))),
                'c="d"',
            ),
            'custom value separator' => array(
                array('a' => 'b'),
                array(array(), ' equals '),
                'a equals "b"',
            ),
            'null as value separator' => array(
                array('a' => 'b'),
                array(array(), null),
                'a"b"',
            ),
            'converted value separator' => array(
                array('a' => 'b'),
                array(array(), new SplFileInfo(' separator ')),
                'a separator "b"',
            ),
            'custom field separator' => array(
                array('a' => 'b', 'c' => 'd'),
                array(array(), '=', ' | '),
                'a="b" | c="d"',
            ),
            'null as field separator' => array(
                array('a' => 'b', 'c' => 'd'),
                array(array(), '=', null),
                'a="b"c="d"',
            ),
            'converted field separator' => array(
                array('a' => 'b', 'c' => 'd'),
                array(array(), '=', new SplFileInfo(' separator ')),
                'a="b" separator c="d"',
            ),
            'custom quote' => array(
                array('a' => 'b'),
                array(array(), '=', '', "'"),
                "a='b'",
            ),
            'null quote' => array(
                array('a' => 'b'),
                array(array(), '=', '', null),
                "a=b",
            ),
            'converted quote' => array(
                array('a' => 'b'),
                array(array(), '=', '', new SplFileInfo('|')),
                "a=|b|",
            ),
        );
    }

    /**
     * Just test, that nothing bad happens, if return value is not used
     */
    public function testSerializeNoReturnValue()
    {
        $object = new Varien_Object(array('a' => 'b'));
        $object->serialize();
    }
}
