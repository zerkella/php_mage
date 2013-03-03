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
            array('_construct', array('protected')),
            array('getData', array('public')),
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

    /**
     * A temp method to test getData(). Will be reworked with the development of setData() and
     * appropriate constructor.
     */
    public function testGetData()
    {
        $reflection = new ReflectionClass('Varien_Object');
        $refProperty = $reflection->getProperty('_data');
        $refProperty->setAccessible(true);

        $object = new Varien_Object();

        // Test that getData() really returns what is needed
        $data = array(1, 2, 3);
        $refProperty->setValue($object, $data);
        $returned = $object->getData();
        $this->assertEquals($data, $returned);

        // Test that returned values is not linked with internal one
        $returned[] = 4;
        $newReturned = $object->getData();
        $this->assertNotEquals($newReturned, $returned);
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
                "_initOldFieldsMap()\n_prepareSyncFieldsMap()\n_construct(): 1,2,3\n"
            ),
            array(
                'Varien_Object_Descendant_Invocation_Constructor_WithoutOldFieldsMap',
                array(4, 5, 6),
                "_initOldFieldsMap()\n_construct(): 4,5,6\n"
            ),
        );
    }
}
