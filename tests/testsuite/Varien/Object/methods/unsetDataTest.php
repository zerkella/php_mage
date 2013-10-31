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
            'converted to string key' => array(
                array('a' => 'b', 5 => 6),
                new SplFileInfo('a'),
                array(5 => 6),
            ),
            'converted to int string key' => array(
                array('a' => 'b', 5 => 6),
                new SplFileInfo(5),
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
            'string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                'a',
                array(111 => '111_value', 222 => '111_value'),
            ),
            'string int key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                '111',
                array('a' => 'a_value', 'b' => 'a_value'),
            ),
            'int key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                111,
                array('a' => 'a_value', 'b' => 'a_value'),
            ),
            'converted to string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                new SplFileInfo('a'),
                array(111 => '111_value', 222 => '111_value'),
            ),
            'converted to int string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                new SplFileInfo(111),
                array('a' => 'a_value', 'b' => 'a_value'),
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
        $key = new SplFileInfo('key');
        $object = new Varien_Object();
        $object->unsetData($key);
        $this->assertInstanceOf('SplFileInfo', $key);
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
}
