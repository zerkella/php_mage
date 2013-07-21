<?php
/**
 * General tests for Varien_Object
 */
class Varien_ObjectTest extends PHPUnit_Framework_TestCase
{
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
            array('isDeleted', array('public')),
            array('getIdFieldName', array('public')),
            array('setIdFieldName', array('public')),
            array('getId', array('public')),
            array('setId', array('public')),
            array('addData', array('public')),
            array('unsetData', array('public')),
            array('unsetOldData', array('public')),
            array('_getData', array('protected')),
            array('setDataUsingMethod', array('public')),
            array('getDataUsingMethod', array('public')),
            array('getDataSetDefault', array('public')),
            array('hasData', array('public')),
            array('__toArray', array('public')),
            array('toArray', array('public')),
            array('_prepareArray', array('protected')),
            array('__toXml', array('protected')),
            array('toXml', array('public')),
            array('__toJson', array('protected')),
            array('toJson', array('public')),
            array('toString', array('public')),
            array('__call', array('public')),
            array('__get', array('public')),
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
        $descendant = new Zerkella_PhpMage_Varien_Object_Descendant_Properties();

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
}
