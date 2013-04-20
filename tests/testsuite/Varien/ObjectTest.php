<?php
/**
 * Test of Varien_Object, which is implemented by 'php_mage' extension
 */
class Varien_ObjectTest extends PHPUnit_Framework_TestCase
{
    static public function setUpBeforeClass()
    {
        // Setup autloader to load tested descendants automatically
        $autoloader = function ($class) {
            $myPrefix = 'Varien_Object_Descendant_';
            if (strncmp($class, $myPrefix, strlen($myPrefix)) != 0) {
                return;
            }
            $classNameBody = substr($class, strlen($myPrefix));
            $subPath = str_replace('_', '/', $classNameBody);
            include __DIR__ . '/_files/Descendant/' . $subPath . '.php';
        };
        spl_autoload_register($autoloader);
    }

    public function assertPreConditions()
    {
        $this->assertTrue(extension_loaded('mage'), 'Extension "mage" is not loaded');
        $this->assertTrue(class_exists('Varien_Object', false),
            'Class Varien_Object must be available, and without autoload');
    }

    /**
     * Test existence of all class methods
     *
     * @param string $method
     * @param array $modifiers
     * @dataProvider methodsDataProvider
     */
    public function testMethods($method, $modifiers)
    {
        $reflection = new ReflectionClass('Varien_Object');
        $refMethod = $reflection->getMethod($method);
        $this->assertNotEmpty($refMethod, "Method '$method' doesn't exist");

        $actualModifiers = Reflection::getModifierNames($refMethod->getModifiers());
        $this->assertEquals($modifiers, $actualModifiers, 'Modifiers do not match');
    }

    public static function methodsDataProvider()
    {
        return array(
            array('__construct', array('public')),
            array('_initOldFieldsMap', array('protected')),
            array('_prepareSyncFieldsMap', array('protected')),
            array('_addFullNames', array('protected')),
            array('_construct', array('protected')),
            array('getData', array('public')),
            array('setData', array('public')),
            array('hasDataChanges', array('public')),
        );
    }

    /**
     * Test default state of class properties
     *
     * @param string $property
     * @param array $modifiers
     * @param string $defaultValue
     * @dataProvider propertiesDataProvider
     */
    public function testProperties($property, $modifiers, $defaultValue)
    {
        $this->assertClassHasAttribute($property, 'Varien_Object');

        $reflection = new ReflectionClass('Varien_Object');
        $refProperty = $reflection->getProperty($property);
        $actualModifiers = Reflection::getModifierNames($refProperty->getModifiers());
        $this->assertEquals($modifiers, $actualModifiers, 'Modifiers do not match');

        $obj = new Varien_Object();
        $this->assertObjectHasAttribute($property, $obj);
        $this->assertAttributeSame($defaultValue, $property, $obj, "Property {$property} has wrong default value");
    }

    /**
     * @return array
     */
    public static function propertiesDataProvider()
    {
        return array(
            array('_data', array('protected'), array()),
            array('_hasDataChanges', array('protected'), false),
            array('_origData', array('protected'), null),
            array('_idFieldName', array('protected'), null),
            array('_underscoreCache', array('protected'), array()),
            array('_isDeleted', array('protected'), false),
            array('_oldFieldsMap', array('protected'), array()),
            array('_syncFieldsMap', array('protected'), array()),
        );
    }

    /**
     * Test that array properties are not suddenly reference each other (this may happen because of internal
     * implementation)
     */
    public function testPropertiesNotLinked()
    {
        $property1 = '_data';
        $property2 = '_underscoreCache';

        $obj = new Varien_Object();
        $this->assertAttributeSame(array(), $property1, $obj, "Property {$property1} has wrong default value");
        $this->assertAttributeSame(array(), $property2, $obj, "Property {$property2} has wrong default value");

        $reflection = new ReflectionClass('Varien_Object');
        $refProperty1 = $reflection->getProperty($property1);
        $refProperty1->setAccessible(true);
        $refProperty1->setValue($obj, array(1, 2, 3));

        $this->assertAttributeSame(array(), $property2, $obj,
            "Properties {$property2} and {$property1} are linked to each other");
    }

    /**
     * Verify, that descendant class can declare new and redeclare properties
     *
     * @param string $property
     * @param mixed $expected
     * @dataProvider descendantPropertiesDataProvider
     */
    public function testDescendantProperties($property, $expected)
    {
        $descendant = new Varien_Object_Descendant_Properties();

        $this->assertObjectHasAttribute($property, $descendant);
        $this->assertAttributeSame($expected, $property, $descendant);
    }

    /**
     * @return array
     */
    public static function descendantPropertiesDataProvider()
    {
        return array(
            '_data' =>              array('_data', array()),
            '_hasDataChanges' =>    array('_hasDataChanges', false),
            '_origData' =>          array('_origData', null),
            '_idFieldName' =>       array('_idFieldName', 'some_id'),
            '_underscoreCache' =>   array('_underscoreCache', 123),
            '_isDeleted' =>         array('_isDeleted', null),
            '_oldFieldsMap' =>      array('_oldFieldsMap', array(4, 5, 6)),
            '_newProperty' =>       array('_newProperty', array(7, 8, 9)),
        );
    }

    public function testConstructor()
    {
        // Default param
        $object = new Varien_Object();
        $this->assertEquals(array(), $object->getData(), 'Default data must be array');

        // Passing param and implicit linking
        $data = array('1', '2', '3');
        $object = new Varien_Object($data);
        $this->assertEquals($data, $object->getData(), 'Data passed via constructor is not preserved');

        $data[] = '4';
        $this->assertEquals(array('1', '2', '3'), $object->getData(),
            'Data after constructor is somehow linked to the originally passed variable');

        // Passing param and implicit linking of referenced value
        $data = array('1', '2', '3');
        $dataRef = &$data;
        $object = new Varien_Object($data);

        $dataRef[] = '4';
        $this->assertEquals(array('1', '2', '3'), $object->getData(),
            'Data after constructor is somehow linked to the originally passed variable, which is referenced');

        // Passing param and implicit linking of referenced value
        $data = array('1', '2', '3');
        $dataRef = &$data;
        $object = new Varien_Object($dataRef);

        $dataRef[] = '4';
        $this->assertEquals(array('1', '2', '3'), $object->getData(),
            'Data after constructor is somehow linked to the originally passed variable with reference');
    }


    /**
     * Test reaction on $_oldFieldsMap property in constructor - the $_syncFieldsMap must be properly composed
     *
     * @param string $className
     * @param bool $expectedSyncFieldsMap
     * @dataProvider oldFieldsMapProcessingDataProvider
     */
    public function testOldFieldsMapProcessing($className, $expectedSyncFieldsMap)
    {
        $obj = new $className();
        $this->assertAttributeSame($expectedSyncFieldsMap, '_syncFieldsMap', $obj);
    }

    /**
     * @return array
     */
    public static function oldFieldsMapProcessingDataProvider()
    {
        return array(
            'Varien_Object' => array(
                'Varien_Object',
                array()
            ),
            'dynamic $_oldFieldsMap' => array(
                'Varien_Object_Descendant_OldFieldsMap_Dynamic',
                array('a' => 'b', 'c' => 'd', 111 => 222, 'b' => 'a', 'd' => 'c', 222 => 111)
            ),
            'static $_oldFieldsMap' => array(
                'Varien_Object_Descendant_OldFieldsMap_Static',
                array('e' => 'f', 'g' => 'h', 333 => 444, 'f' => 'e', 'h' => 'g', 444 => 333)
            ),
        );
    }

    public function testPrepareSyncFieldsMap()
    {
        $reflection = new ReflectionClass('Varien_Object');
        $refMethod = $reflection->getMethod('_prepareSyncFieldsMap');
        $refMethod->setAccessible(true);

        $object = new Varien_Object();
        $result = $refMethod->invoke($object);
        $this->assertSame($object, $result);
    }

    /**
     * @param string $className
     * @param array $initialData
     * @param string $expectedOutput
     * @dataProvider constructorInvocationDataProvider
     */
    public function testConstructorInvocation($className, $initialData, $expectedOutput)
    {
        $this->expectOutputString($expectedOutput);
        new $className($initialData);
    }

    /**
     * @return array
     */
    public static function constructorInvocationDataProvider()
    {
        return array(
            array(
                'Varien_Object_Descendant_Invocation_Constructor_General',
                array(1, 2, 3),
                "_initOldFieldsMap()\n_prepareSyncFieldsMap()\n_addFullNames(): 1,2,3\n_construct(): 1,2,3\n"
            ),
            array(
                'Varien_Object_Descendant_Invocation_Constructor_WithoutOldFieldsMap',
                array(4, 5, 6),
                "_initOldFieldsMap()\n_addFullNames(): 4,5,6\n_construct(): 4,5,6\n"
            ),
        );
    }

    /**
     * @param array $data
     * @param array $expected
     * @dataProvider addFullNamesDataProvider
     */
    public function testAddFullNames($data, $expected)
    {
        $object = new Varien_Object_Descendant_AddFullNames($data);
        $actual = $object->getData();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public static function addFullNamesDataProvider()
    {
        $data = array(
            'old_property1' => 'old',
            'new_property2' => 'new',
            'some_property' => 'some_value',
            111 => 99,
        );

        $dataWithReferences = $data;
        $dataWithReferences['old_property1'] = &$dataWithReferences['some_property'];

        $dataReferenced = $data;
        $dataReferenced['some_property'] = &$dataReferenced['old_property1'];

        return array(
            'usual data' => array(
                'data' => $data,
                'expected' => array(
                    'old_property1' => 'old',
                    'new_property2' => 'new',
                    'some_property' => 'some_value',
                    111 => 99,
                    'new_property1' => 'old',
                    333 => 99,
                    'old_property2' => 'new',
                ),
            ),
            'data with references' => array(
                'data' => $dataWithReferences,
                'expected' => array(
                    'old_property1' => 'some_value',
                    'new_property2' => 'new',
                    'some_property' => 'some_value',
                    111 => 99,
                    'new_property1' => 'some_value',
                    333 => 99,
                    'old_property2' => 'new',
                ),
            ),
            'data referenced' => array(
                'data' => $dataReferenced,
                'expected' => array(
                    'old_property1' => 'old',
                    'new_property2' => 'new',
                    'some_property' => 'old',
                    111 => 99,
                    'new_property1' => 'old',
                    333 => 99,
                    'old_property2' => 'new',
                ),
            ),
        );
    }

    public function testAddFullNamesWithReferenceChanges()
    {
        $oldProperty = 'a';
        $oldNumProperty = 1;
        $data = array('old_property1' => &$oldProperty, 111 => &$oldNumProperty);
        $object = new Varien_Object_Descendant_AddFullNames($data);

        // Change referenced values
        $oldProperty = 'b';
        $oldNumProperty = 2;

        // Verify, that referenced values (and only them) have changed inside the object
        $actual = $object->getData();
        $expected = array(
            'old_property1' => 'b',
            'new_property1' => 'a',
            111 => 2,
            333 => 1,
        );
        $this->assertEquals($expected, $actual);
    }

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

    public function testSetDataRaisesChangesFlag()
    {
        $object = new Varien_Object();
        $this->assertFalse($object->hasDataChanges(), 'Object must be non-changed by default');
        $object->setData(array());
        $this->assertTrue($object->hasDataChanges(), 'Object must has changes after setting data to it');
    }

    public function testSetDataReturnsSelf()
    {
        $object = new Varien_Object();
        $this->assertSame($object, $object->setData(array()));
    }

    /**
     * @param string $class
     * @param array $data
     * @param array $expectedData
     * @dataProvider setDataSingleArgumentDataProvider
     */
    public function testSetDataSingleArgument($class, $data, $expectedData)
    {
        $object = new $class();
        $object->setData($data);
        $actualData = $object->getData();
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public static function setDataSingleArgumentDataProvider()
    {
        return array(
            'ordinary set data' => array (
                'Varien_Object',
                array('a' => 'b', 1 => 2),
                array('a' => 'b', 1 => 2),
            ),
            'dynamic old fields map' => array(
                'Varien_Object_Descendant_OldFieldsMap_Dynamic',
                array('a' => 'a_value', 222 => '222_value', 'just_a_key' => 'just_a_value'),
                array('a' => 'a_value', 'b' => 'a_value', 222 => '222_value', 111 => '222_value',
                    'just_a_key' => 'just_a_value'),
            ),
            'static old fields map' => array(
                'Varien_Object_Descendant_OldFieldsMap_Static',
                array('h' => 'h_value', 333 => '333_value', 'just_a_key' => 'just_a_value'),
                array('h' => 'h_value', 'g' => 'h_value', 333 => '333_value', 444 => '333_value',
                    'just_a_key' => 'just_a_value'),
            ),
        );
    }

    public function testSetDataSingleArgumentWithRefcount()
    {
        $data = array('a' => 'b');
        $dataCopy = $data;

        $object = new Varien_Object();
        $object->setData($dataCopy);
        $data['a'] = 'c';
        $actualData = $object->getData();

        $this->assertEquals(array('a' => 'b'), $actualData, 'setData() must not keep references to the passed value');
    }

    public function testSetDataSingleArgumentWithIsRef()
    {
        $data = array('a' => 'b');
        $dataCopy = &$data;

        $object = new Varien_Object();
        $object->setData($dataCopy);
        $data['a'] = 'c';
        $actualData = $object->getData();

        $this->assertEquals(array('a' => 'b'), $actualData,
            'setData() must not keep references to the passed value with reference link');
    }

    /**
     * @param string $class
     * @param mixed $key
     * @param mixed $value
     * @param array $expected
     * @dataProvider setDataDataProvider
     */
    public function testSetData($class, $key, $value, array $expected)
    {
        $origKey = is_object($key) ? clone $key : unserialize(serialize($key)); // Just to break any references to key

        $object = new $class;
        $object->setData($key, $value);
        $actual = $object->getData();
        $this->assertSame($expected, $actual);

        // Key value must not change, even when converted to string
        if (is_object($origKey)) {
            $this->assertInternalType('object', $key, 'Key object must not be converted');
            $this->assertEquals($origKey, $key, 'Key data must stay the same for object');
        } else {
            $this->assertSame($origKey, $key, 'Key data must stay the same');
        }
    }

    public static function setDataDataProvider()
    {
        return array(
            'integer key' => array(
                'Varien_Object',
                11,
                'value',
                array(11 => 'value')
            ),
            'string key' => array(
                'Varien_Object',
                'key',
                'value',
                array('key' => 'value')
            ),
            'string integer key' => array(
                'Varien_Object',
                '11',
                'value',
                array(11 => 'value')
            ),
            'null key' => array(
                'Varien_Object',
                null,
                'value',
                array('' => 'value')
            ),
            'object key' => array(
                'Varien_Object',
                new SplFileInfo('key'),
                'value',
                array('key' => 'value')
            ),
        );
    }

    public function testSetDataWithRefCount()
    {
        $data = 'value';
        $value = $data;
        $object = new Varien_Object;
        $object->setData('key', $value);
        $this->assertSame('value', $object->getData('key'));

        $data = 'new value';
        $this->assertSame('value', $object->getData('key'));
    }

    public function testSetDataWithReference()
    {
        $data = 'value';
        $value = &$data;
        $object = new Varien_Object;
        $object->setData('key', $value);
        $this->assertSame('value', $object->getData('key'));

        $data = 'new value';
        $this->assertSame('value', $object->getData('key'));
    }

    public function testSetDataWithObjectReference()
    {
        $value = new SplFileInfo('file');
        $object = new Varien_Object;
        $object->setData('key', $value);
        $this->assertSame($value, $object->getData('key'));
    }
}
