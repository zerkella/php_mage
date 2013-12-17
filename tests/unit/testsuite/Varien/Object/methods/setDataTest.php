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
                array('a' => 'a_value', 'd' => 'd_value', 'just_a_key' => 'just_a_value'),
                array('a' => 'a_value', 'b' => 'a_value', 'd' => 'd_value', 'c' => 'd_value',
                    'just_a_key' => 'just_a_value'),
            ),
            'static old fields map' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Static',
                array('h' => 'h_value', 'e' => 'e_value', 'just_a_key' => 'just_a_value'),
                array('h' => 'h_value', 'g' => 'h_value', 'e' => 'e_value', 'f' => 'e_value',
                    'just_a_key' => 'just_a_value'),
            ),
        );
    }

    /**
     * Test for a case, when there are numbered old fields.
     * This use case is never used in Magento (moreover - it has bugs there), so the test just ensures, that
     * no exceptions or segfaults occur because of such call
     *
     * @param string $class
     * @param array $data
     * @dataProvider setDataSingleArgumentWithNumbersDataProvider
     */
    public function testSetDataWithNumbersSingleArgument($class, $data)
    {
        $object = new $class();
        $result = $object->setData($data);
    }

    /**
     * @return array
     */
    public static function setDataSingleArgumentWithNumbersDataProvider()
    {
        return array(
            'ordinary set data' => array (
                'Varien_Object',
                array('a' => 'b', 1 => 2),
                array('a' => 'b', 1 => 2),
            ),
            'dynamic old fields map' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_DynamicWithNumbers',
                array('a' => 'a_value', 222 => '222_value', 'just_a_key' => 'just_a_value'),
                array('a' => 'a_value', 'b' => 'a_value', 222 => '222_value', 0 => '222_value',
                    'just_a_key' => 'just_a_value'),
            ),
            'static old fields map' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_StaticWithNumbers',
                array('h' => 'h_value', 333 => '333_value', 'just_a_key' => 'just_a_value'),
                array('h' => 'h_value', 'g' => 'h_value', 333 => '333_value', 1 => '333_value',
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

    /**
     * @return array
     */
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
            'sync with string key' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames',
                'new_property2',
                'value',
                array('new_property2' => 'value', 'old_property2' => 'value')
            ),
        );
    }

    /**
     * Test for a case, when there are numbered old fields.
     * This use case is never used in Magento (moreover - it has bugs there), so the test just ensures, that
     * no exceptions or segfaults occur because of such call
     *
     * @param mixed $key
     * @param mixed $value
     * @dataProvider setDataWithNumbersDataProvider
     */
    public function testSetDataWithNumbers($key, $value)
    {
        $origKey = is_object($key) ? clone $key : unserialize(serialize($key)); // Just to break any references to key

        $object = new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNamesWithNumbers();
        $object->setData($key, $value);

        // Key value must not change, even when converted to string
        if (is_object($origKey)) {
            $this->assertInternalType('object', $key, 'Key object must not be converted');
            $this->assertEquals($origKey, $key, 'Key data must stay the same for object');
        } else {
            $this->assertSame($origKey, $key, 'Key data must stay the same');
        }
    }

    /**
     * @return array
     */
    public static function setDataWithNumbersDataProvider()
    {
        // The numbers in old fields are buggy, logic not defined well and may depend on implementation
        return array(
            'sync with 111 integer key possibly existing' => array(
                111,
                'value',
            ),
            'sync with 111 string integer possibly key' => array(
                '111',
                'value',
            ),
            'sync with 0 integer key possibly existing' => array(
                0,
                'value',
            ),
            'sync with 0 string integer key possibly existing' => array(
                '0',
                'value',
            ),
            'sync with 1 integer key possibly existing' => array(
                1,
                'value',
            ),
            'sync with 1 string integer key possibly existing' => array(
                '1',
                'value',
            ),
            'sync with 333 integer key possibly existing' => array(
                333,
                'value',
            ),
            'sync with 333 string integer key possibly existing' => array(
                '333',
                'value',
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

    public function testSetDataRaisesHasDataChanges()
    {
        $object = new Varien_Object();
        $this->assertFalse($object->hasDataChanges());
        $object->setData('a', 'b');
        $this->assertTrue($object->hasDataChanges());
    }

    /**
     * Test that a warning is correctly reported, when $key parameter is not passed
     *
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testSetDataNoParams()
    {
        $object = new Varien_Object();
        $object->setData();
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testSetDataWithNonScalarParam()
    {
        $objParam = new SplFileInfo('key');
        $object = new Varien_Object();
        $object->setData($objParam, 'value');
    }
}
