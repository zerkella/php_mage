<?php
/**
 * Test of Varien_Object, which is implemented by 'php_mage' extension
 */
class Varien_ObjectTest extends PHPUnit_Framework_TestCase
{
    public function assertPreConditions()
    {
        $this->assertTrue(extension_loaded('mage'), 'Extension "mage" is not loaded');
        $this->assertTrue(class_exists('Varien_Object', false),
            'Class Varien_Object must be available, and without autoload');
    }

    /**
     * Test default state of class properties
     *
     * @param string $property
     * @param array $modifiers
     * @param string $defaultValue
     * @dataProvider internalPropertiesDataProvider
     */
    public function testInternalProperties($property, $modifiers, $defaultValue)
    {
        $reflection = new ReflectionClass('Varien_Object');
        $refProperty = $reflection->getProperty($property);
        $this->assertNotEmpty($refProperty, "Property {$property} doesn't exist");

        $actualModifiers = Reflection::getModifierNames($refProperty->getModifiers());
        $this->assertEquals($modifiers, $actualModifiers, 'Modifiers do not match');

        $this->assertSame($defaultValue, $refProperty->getValue());
    }

    public static function internalPropertiesDataProvider()
    {
        return array(
            array('_data', array('protected'), array()),
        );
    }
}
