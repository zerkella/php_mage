<?php
class Varien_Object_methods_getDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $dataToPass
     * @param array $params
     * @param mixed $expectedResult
     * @dataProvider getDataDataProvider
     */
    public function testGetData($dataToPass, $params, $expectedResult)
    {
        $object = new Varien_Object($dataToPass);
        $actualResult = call_user_func_array(array($object, 'getData'), $params);

        // Test that getData() really returns what is needed
        $this->assertSame($expectedResult, $actualResult);

        // Test that returned value is not linked with internal one
        $actualResult .= 'some_additional_value';
        $newResult = call_user_func_array(array($object, 'getData'), $params);
        $this->assertNotEquals($newResult, $actualResult);
    }

    public static function getDataDataProvider()
    {
        return array(
            'whole data' => array(
                array(1, 2, 3),
                array(),
                array(1, 2, 3),
            ),
            'whole data with 1 actual param' => array(
                array(1, 2, 3),
                array(''),
                array(1, 2, 3),
            ),
            'whole data with 2 actual params' => array(
                array(1, 2, 3),
                array('', ''),
                array(1, 2, 3),
            ),
            'key path' => array(
                array(
                    'some_data',
                    'path' => array(
                        'to' => array(
                            'value' => 'retrieved_data'
                        ),
                    ),
                    'another_data'
                ),
                array('path/to/value'),
                'retrieved_data',
            ),
            'key path with integers' => array(
                array(
                    'some_data',
                    '1' => array(
                        '2' => array(
                            '3' => 'retrieved_data'
                        ),
                    ),
                    'another_data'
                ),
                array('1/2/3'),
                'retrieved_data',
            ),
            'key path to Varien_Objects' => array(
                array(
                    'some_data',
                    'path' => new Varien_Object(
                        array(
                            'to' => new Varien_Object(array('data' => 'retrieved_data')),
                        )
                    ),
                    'another_data'
                ),
                array('path/to/data'),
                'retrieved_data',
            ),
            'wrong key path, ending with slash' => array(
                array(
                    'some_data',
                    'path' => array(
                        'to' => array(
                            'value' => 'retrieved_data'
                        ),
                    ),
                    'another_data'
                ),
                array('path/to/'),
                null,
            ),
            'wrong key path, with middle slash' => array(
                array(
                    'some_data',
                    'path' => array(
                        'to' => array(
                            'value' => 'retrieved_data'
                        ),
                    ),
                    'another_data'
                ),
                array('path//value'),
                null,
            ),
            'wrong value type for key path' => array(
                array(
                    'some_data',
                    'path' => array(
                        'to' => 2
                    ),
                    'another_data'
                ),
                array('path/to/value'),
                null,
            ),
            'absent value type for key path' => array(
                array(
                    'some_data',
                    'path' => array(
                        'to' => array('a' => 'b')
                    ),
                    'another_data'
                ),
                array('path/to/value'),
                null,
            ),
            'get data by string key' => array(
                array(
                    'one' => 1,
                    'two' => 2,
                    3 => 4,
                ),
                array('two'),
                2
            ),
            'get data by int key' => array(
                array(
                    'one' => 1,
                    'two' => 2,
                    3 => 4,
                ),
                array(3),
                4
            ),
            'get data by null key' => array(
                array('' => 'a'),
                array(null),
                'a'
            ),
            'get data by false key' => array(
                array(0 => 'a'),
                array(false),
                'a'
            ),
            'get data by true key' => array(
                array(1 => 'a'),
                array(true),
                'a'
            ),
            'get data by with null param for index' => array(
                array(
                    'one' => 1,
                    'two' => 2,
                    3 => 4,
                ),
                array('two', null),
                2
            ),
            'get data by non-existing key' => array(
                array(
                    'one' => 1,
                    'two' => 2,
                    3 => 4,
                ),
                array('five'),
                null
            ),
            'get data by key and string index, array value' => array(
                array(
                    'array' => array('one' => 1, 'two' => 2),
                ),
                array('array', 'one'),
                1
            ),
            'get data by key and int index, array value' => array(
                array(
                    'array' => array(1 => 'one', 2 => 'two'),
                ),
                array('array', 1),
                'one',
            ),
            'get data by key and non-existing index, array value' => array(
                array(
                    'array' => array(1 => 'one', 2 => 'two'),
                ),
                array('array', 3),
                null,
            ),
            'get data by key and string index, string value' => array(
                array(
                    'string' => "AAA\nBBB\nCCC",
                ),
                array('string', '2'),
                'CCC'
            ),
            'get data by key and int index, string value' => array(
                array(
                    'string' => "AAA\nBBB\nCCC",
                ),
                array('string', 2),
                'CCC',
            ),
            'get data by key and non-existing index, string value' => array(
                array(
                    'string' => "AAA\nBBB\nCCC",
                ),
                array('array', 3),
                null,
            ),
            'get data by key and non-existing non-number index, string value' => array(
                array(
                    'string' => "AAA\nBBB\nCCC",
                ),
                array('array', "not_existing"),
                null,
            ),
            'get data by key and index, string empty value' => array(
                array(
                    'string' => '',
                ),
                array('array', 0),
                null,
            ),
            'get data by key and index, corresponding empty value in string' => array(
                array(
                    'string' => "AAA\n\nBBB",
                ),
                array('array', 1),
                null,
            ),
            'get data by key and string index, Varien_Object value' => array(
                array(
                    'array' => new Varien_Object(array('one' => 1, 'two' => 2)),
                ),
                array('array', 'one'),
                1
            ),
            'get data by key and int index, Varien_Object value' => array(
                array(
                    'array' => new Varien_Object(array(1 => 'one', 2 => 'two')),
                ),
                array('array', 1),
                'one',
            ),
            'get data by key and non-existing index, Varien_Object value' => array(
                array(
                    'array' => new Varien_Object(array(1 => 'one', 2 => 'two')),
                ),
                array('array', 3),
                null,
            ),
            'get data by key and index, non-supported bool value' => array(
                array(
                    'bool' => true,
                ),
                array('bool', 0),
                null,
            ),
            'get data by key and index, non-supported object value' => array(
                array(
                    'obj' => new StdClass(),
                ),
                array('obj', 0),
                null,
            ),
        );
    }

    /**
     * Test, that when the object calls getData() in other object, and there is an exception,
     * then everything goes fine.
     *
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @param array $args
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     * @dataProvider getDataSubExceptionDataProvider
     */
    public function testGetDataSubException($args)
    {
        $subObject = $this->getMock('Varien_Object', array('getData'));
        $subObject->expects($this->once())
            ->method('getData')
            ->with('b', null)
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $object = new Varien_Object(array('a' => $subObject));
        $result = call_user_func_array(array($object, 'getData'), $args);
    }

    /**
     * @return array
     */
    public static function getDataSubExceptionDataProvider()
    {
        return array(
            'path key' => array(
                array('a/b'),
            ),
            'key and index' => array(
                array('a', 'b'),
            ),
        );
    }
}
