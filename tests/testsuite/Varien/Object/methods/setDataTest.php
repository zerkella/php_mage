<?php
class Varien_Object_methods_setDataTest extends PHPUnit_Framework_TestCase
{
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
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic',
                array('a' => 'a_value', 222 => '222_value', 'just_a_key' => 'just_a_value'),
                array('a' => 'a_value', 'b' => 'a_value', 222 => '222_value', 111 => '222_value',
                    'just_a_key' => 'just_a_value'),
            ),
            'static old fields map' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Static',
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
            'sync with integer key' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames',
                111,
                'value',
                array(111 => 'value', 333 => 'value')
            ),
            'sync with string integer key' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames',
                '111',
                'value',
                array(111 => 'value', 333 => 'value')
            ),
            'sync with string key' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames',
                'new_property2',
                'value',
                array('new_property2' => 'value', 'old_property2' => 'value')
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

    public function testSetDataOriginalDataMustBeIntactWhenKeyPassed()
    {
        $data = array('a' => 'b');
        $object = new Varien_Object($data);
        $object->setData(1, 2);
        $this->assertSame(array('a' => 'b'), $data);
    }

    public function testSetDataOriginalDataMustBeIntactWhenArrayPassed()
    {
        $data = array('a' => 'b');
        $object = new Varien_Object($data);
        $object->setData(array(2));
        $this->assertSame(array('a' => 'b'), $data);
    }
}
