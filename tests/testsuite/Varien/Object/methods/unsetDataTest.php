<?php
class Varien_Object_methods_unsetDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $initialData
     * @param string|null $key
     * @param mixed $expectedData
     * @dataProvider unsetDataDataProvider
     */
    public function testUnsetData($initialData, $key, $expectedData)
    {
        $object = new Varien_Object($initialData);
        $result = $object->unsetData($key);
        $actualData = $object->getData();

        $this->assertSame($expectedData, $actualData);
        $this->assertTrue($object->hasDataChanges());
        $this->assertSame($result, $object);
    }

    public static function unsetDataDataProvider()
    {
        return array(
            'null key' => array(
                array('a' => 'b', 5 => 6),
                null,
                array(),
            ),
            'string key' => array(
                array('a' => 'b', 5 => 6),
                'a',
                array(5 => 6),
            ),
            'string int key' => array(
                array('a' => 'b', 5 => 6),
                '5',
                array('a' => 'b'),
            ),
            'int key' => array(
                array('a' => 'b', 5 => 6),
                5,
                array('a' => 'b'),
            ),
            'non-existing string key' => array(
                array('a' => 'b', 5 => 6),
                'c',
                array('a' => 'b', 5 => 6),
            ),
            'non-existing int key' => array(
                array('a' => 'b', 5 => 6),
                3,
                array('a' => 'b', 5 => 6),
            ),
            'true key' => array(
                array(1 => 2, 3 => 4),
                true,
                array(3 => 4),
            ),
            'bool key' => array(
                array(0 => 1, 2 => 3),
                false,
                array(2 => 3),
            ),
        );
    }

    /**
     * @param array $initialData
     * @param string|null $key
     * @param mixed $expectedData
     * @dataProvider unsetDataWithSyncedFieldsDataProvider
     */
    public function testUnsetDataWithSyncedFields($initialData, $key, $expectedData)
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic($initialData);
        $object->unsetData($key);
        $actualData = $object->getData();
        $this->assertSame($expectedData, $actualData);
    }

    public static function unsetDataWithSyncedFieldsDataProvider()
    {
        return array(
            'new key' => array(
                array('a' => 'a_value', 'e' => 'f'),
                'a',
                array('e' => 'f'),
            ),
            'old key' => array(
                array('a' => 'a_value', 'e' => 'f'),
                'b',
                array('e' => 'f'),
            ),
        );
    }

    /**
     * Test for a case, when there are numbered old fields.
     * This use case is never used in Magento (moreover - it has bugs there), so the test just ensures, that
     * no exceptions or segfaults occur because of such call
     *
     * @param array $initialData
     * @param string|null $key
     * @dataProvider unsetDataWithSyncedFieldsWithNumbersDataProvider
     */
    public function testUnsetDataWithSyncedFieldsWithNumbers($initialData, $key)
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_DynamicWithNumbers($initialData);
        $object->unsetData($key);
    }

    /**
     * @return array
     */
    public static function unsetDataWithSyncedFieldsWithNumbersDataProvider()
    {
        return array(
            'string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                'a',
            ),
            'string int key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                '111',
            ),
            'int key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                111,
            ),
        );
    }

    public function testUnsetDataNullKeyUnsetsAll()
    {
        $object = new Varien_Object(array(1, 2));
        $result = $object->unsetData();

        $this->assertSame(array(), $object->getData());
        $this->assertTrue($object->hasDataChanges());
        $this->assertSame($result, $object);
    }

    public function testUnsetDataDoesntSpoilKeyParam()
    {
        $key = true;
        $keyRef = &$key;
        $object = new Varien_Object(array(1 => 2));
        $object->unsetData($keyRef);
        $this->assertTrue($key);
    }

    public function testDataIsNotLinkedForUnset()
    {
        $data = array('a' => 'b', 5 => 6);
        $object = new Varien_Object($data);
        $object->unsetData('a');

        $this->assertSame(array('a' => 'b', 5 => 6), $data);
        $this->assertSame(array(5 => 6), $object->getData());
    }

    public function testDataIsNotLinkedForUnsetWithNull()
    {
        $data = array('a' => 'b', 5 => 6);
        $object = new Varien_Object($data);
        $object->unsetData();

        $this->assertSame(array('a' => 'b', 5 => 6), $data);
    }

    public function testUnsetDataRaisesHasDataChanges()
    {
        $object = new Varien_Object();
        $this->assertFalse($object->hasDataChanges());
        $object->unsetData('a');
        $this->assertTrue($object->hasDataChanges());
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testUnsetDataWithNonScalarParam()
    {
        $objParam = new SplFileInfo('a');
        $object = new Varien_Object(array('a' => 'b'));
        $object->unsetData($objParam);
    }
}
