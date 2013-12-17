<?php
class Varien_Object_methods_offsetSetTest extends PHPUnit_Framework_TestCase
{

    /**
     * @param mixed $offset
     * @param mixed $dataIndexRead
     * @dataProvider offsetSetDataProvider
     */
    public function testOffsetSet($offset, $dataIndexRead)
    {
        $value = new StdClass;
        $object = new Varien_Object();
        $object->offsetSet($offset, $value);

        $reflectionProperty = new ReflectionProperty('Varien_Object', '_data');
        $reflectionProperty->setAccessible(true);
        $data = $reflectionProperty->getValue($object);

        $this->assertArrayHasKey($dataIndexRead, $data);
        $this->assertSame($value, $data[$dataIndexRead]);
    }

    /**
     * @return array
     */
    public static function offsetSetDataProvider()
    {
        return array(
            'string' => array('a', 'a'),
            'int' => array(1, 1),
            'int offset string read' => array(1, '1'),
            'int-string offset, int read' => array('1', 1),
            'int-string offset, string read' => array('1', '1'),
            'true' => array(true, 1),
            'false' => array(false, 0),
            'empty string' => array('', ''),
            'null' => array(null, ''),
        );
    }

    public function testOffsetSetNoDataLinksCopyOnWrite()
    {
        $value = 'a';
        $object = new Varien_Object();
        $object->offsetSet('value', $value);

        $reflectionProperty = new ReflectionProperty('Varien_Object', '_data');
        $reflectionProperty->setAccessible(true);

        $value = 'b';
        $data = $reflectionProperty->getValue($object);

        $this->assertSame($data['value'], 'a');
    }

    public function testOffsetSetNoBackDataLinksCopyOnWrite()
    {
        $value = 'a';
        $object = new Varien_Object();
        $object->offsetSet('value', $value);

        $object->setData('value', 'b');
        $this->assertSame($value, 'a');
    }

    public function testOffsetSetNoDataLinksWhenReferenced()
    {
        $orig = 'a';
        $value = &$orig;
        $object = new Varien_Object();
        $object->offsetSet('value', $value);

        $reflectionProperty = new ReflectionProperty('Varien_Object', '_data');
        $reflectionProperty->setAccessible(true);

        $orig = 'b';
        $data = $reflectionProperty->getValue($object);
        $this->assertSame($data['value'], 'a');

        $value = 'c';
        $data = $reflectionProperty->getValue($object);
        $this->assertSame($data['value'], 'a');
    }

    /**
     * Test that a warning is correctly reported, when no parameters are passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testOffsetSetNoParams()
    {
        $object = new Varien_Object();
        $object->offsetSet();
    }

    /**
     * Test that a warning is correctly reported, when only one parameter is passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testOffsetSetOneParamOny()
    {
        $object = new Varien_Object();
        $object->offsetSet(1);
    }
}
