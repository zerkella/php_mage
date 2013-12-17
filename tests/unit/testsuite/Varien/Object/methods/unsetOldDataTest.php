<?php
class Varien_Object_methods_unsetOldDataTest extends PHPUnit_Framework_TestCase
{

    /**
     * Return whether we're testing Varien_Object, where bug in unsetOldData() is fixed
     *
     * @return bool
     */
    protected static function _isFixedBugWithOldDataNullKey()
    {
        if (!defined('TESTS_MAGENTO_PATH')) {
            return true; // php_mage extension has non-buggy functionality
        }

        $reflectionMethod = new ReflectionMethod('Varien_Object', 'unsetOldData');
        $sourceClass = file($reflectionMethod->getFileName());
        $sourceMethod = array_slice($sourceClass, $reflectionMethod->getStartLine(),
            $reflectionMethod->getEndLine() - $reflectionMethod->getStartLine());
        $source = implode("\n", $sourceMethod);

        return strpos($source, '$this->_oldFieldsMap') !== false;
    }

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
                array('a' => 'a_value', 'e' => 'e_value'),
                null,
                self::_isFixedBugWithOldDataNullKey() ? array('e' => 'e_value', 'b' => 'a_value')
                    : array('e' => 'e_value'),
            ),
            'key' => array(
                array('a' => 'a_value', 'e' => 'e_value'),
                'a',
                array('e' => 'e_value', 'b' => 'a_value'),
            ),
            'non-existing string key' => array(
                array('a' => 'a_value'),
                'c',
                array('a' => 'a_value', 'b' => 'a_value'),
            ),
            'non-old key is unset at all - method does not differentiate old keys from new' => array(
                array('a' => 'a_value', 'e' => 'e_value'),
                'e',
                array('a' => 'a_value', 'b' => 'a_value'),
            )
        );
    }

    /**
     * Test for a case, when there are numbered old fields.
     * This use case is never used in Magento (moreover - it has bugs there), so the test just ensures, that
     * no exceptions or segfaults occur because of such call
     *
     * @param array $initialData
     * @param string|null $key
     * @dataProvider unsetOldDataWithNumbersDataProvider
     */
    public function testUnsetOldDataWithNumbers($initialData, $key)
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_DynamicWithNumbers($initialData);
        $result = $object->unsetOldData($key);
    }

    /**
     * @return array
     */
    public static function unsetOldDataWithNumbersDataProvider()
    {
        return array(
            'string int key' => array(
                array('a' => 'a_value', 0 => '0_value'),
                '222',
            ),
            'int key' => array(
                array('a' => 'a_value', 0 => '0_value'),
                222,
            ),
            'non-existing int key' => array(
                array('a' => 'a_value', 0 => '0_value'),
                3,
            ),
        );
    }

    public function testWithoutKeyUnsetsAllOldKeys()
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic(
            array('a' => 'a_value', 111 => '111_value')
        );
        $result = $object->unsetOldData();

        $expected = self::_isFixedBugWithOldDataNullKey() ? array(111 => '111_value', 'b' => 'a_value')
            : array(111 => '111_value');
        $this->assertSame($expected, $object->getData());
        $this->assertSame($result, $object);
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
        $data = array('a' => 'a_value', 'e' => 'e_value');
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic($data);
        $object->unsetOldData('a');

        $this->assertSame(array('e' => 'e_value', 'b' => 'a_value'), $object->getData());
        $this->assertSame(array('a' => 'a_value', 'e' => 'e_value'), $data);
    }

    public function testNoLinksForUnsetWithoutKey()
    {
        $data = array('a' => 'a_value', 'e' => 'e_value');
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic($data);
        $object->unsetOldData();

        $expected = self::_isFixedBugWithOldDataNullKey() ? array('e' => 'e_value', 'b' => 'a_value')
            : array('e' => 'e_value');
        $this->assertSame($expected, $object->getData());

        $this->assertSame(array('a' => 'a_value', 'e' => 'e_value'), $data);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testUnsetOldDataWithNonScalarParam()
    {
        $objParam = new SplFileInfo('a');
        $object = new Varien_Object(array('a' => 'b'));
        $object->unsetOldData($objParam);
    }
}
