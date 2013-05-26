<?php
class Varien_Object_methods_unsetOldDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $initialData
     * @param string|null $key
     * @param mixed $expectedData
     * @dataProvider unsetOldDataDataProvider
     */
    public function testUnsetOldData($initialData, $key, $expectedData)
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic($initialData);
        $result = $object->unsetOldData($key);
        $actualData = $object->getData();

        $this->assertSame($expectedData, $actualData);
        $this->assertSame($result, $object);
    }

    public static function unsetOldDataDataProvider()
    {
        return array(
            'null key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                null,
                array('b' => 'a_value', 222 => '111_value'),
            ),
            'string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                'a',
                array(111 => '111_value', 'b' => 'a_value', 222 => '111_value'),
            ),
            'string int key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                '111',
                array('a' => 'a_value', 'b' => 'a_value', 222 => '111_value'),
            ),
            'int key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                111,
                array('a' => 'a_value', 'b' => 'a_value', 222 => '111_value'),
            ),
            'converted to string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                new SplFileInfo('a'),
                array(111 => '111_value', 'b' => 'a_value', 222 => '111_value'),
            ),
            'converted to int string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                new SplFileInfo(111),
                array('a' => 'a_value', 'b' => 'a_value', 222 => '111_value'),
            ),
            'non-existing string key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                'c',
                array('a' => 'a_value', 111 => '111_value', 'b' => 'a_value', 222 => '111_value'),
            ),
            'non-existing int key' => array(
                array('a' => 'a_value', 111 => '111_value'),
                3,
                array('a' => 'a_value', 111 => '111_value', 'b' => 'a_value', 222 => '111_value'),
            ),
            'non-old key is unset at all - method does not differentiate old keys from new' => array(
                array('a' => 'a_value', 111 => '111_value'),
                'b',
                array('a' => 'a_value', 111 => '111_value', 222 => '111_value'),
            )
        );
    }

    public function testWithoutKeyUnsetsAllOldKeys()
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic(
            array('a' => 'a_value', 111 => '111_value')
        );
        $result = $object->unsetOldData();

        $this->assertSame(array('b' => 'a_value', 222 => '111_value'), $object->getData());
        $this->assertSame($result, $object);
    }

    public function testUnsetOldDataDoesntSpoilKeyParam()
    {
        $key = new SplFileInfo('key');
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic();
        $object->unsetOldData($key);
        $this->assertInstanceOf('SplFileInfo', $key);
    }

    public function testUnsetOldDataWorksFineWithEmptyOldKeys()
    {
        $object = new Varien_Object(array('a' => 'b', 3 => 4));
        $object->unsetOldData();
        $object->unsetOldData('b');
        $this->assertSame(array('a' => 'b', 3 => 4), $object->getData());
    }

    public function testNoLinksForUnsetWithKey()
    {
        $data = array('a' => 'a_value', 111 => '111_value');
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic($data);
        $object->unsetOldData('a');

        $this->assertSame(array(111 => '111_value', 'b' => 'a_value', 222 => '111_value'), $object->getData());
        $this->assertSame(array('a' => 'a_value', 111 => '111_value'), $data);
    }

    public function testNoLinksForUnsetWithoutKey()
    {
        $data = array('a' => 'a_value', 111 => '111_value');
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic($data);
        $object->unsetOldData();

        $this->assertSame(array('b' => 'a_value', 222 => '111_value'), $object->getData());
        $this->assertSame(array('a' => 'a_value', 111 => '111_value'), $data);
    }
}
