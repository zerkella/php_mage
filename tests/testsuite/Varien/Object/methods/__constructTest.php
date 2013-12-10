<?php
class Varien_Object_methods___constructTest extends PHPUnit_Framework_TestCase
{
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
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic',
                array('a' => 'b', 'c' => 'd', 'b' => 'a', 'd' => 'c')
            ),
            'static $_oldFieldsMap' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Static',
                array('e' => 'f', 'g' => 'h', 'f' => 'e', 'h' => 'g')
            ),
        );
    }

    /**
     * Test for a case, when there are numbered old fields.
     * This use case is never used in Magento (moreover - it has bugs there), so the test just ensures, that
     * no exceptions or segfaults occur because of such object
     *
     * @param string $className
     * @dataProvider oldFieldsMapProcessingWithNumbersDataProvider
     */
    public function testOldFieldsMapProcessingWithNumbers($className)
    {
        $obj = new $className();
    }

    /**
     * @return array
     */
    public static function oldFieldsMapProcessingWithNumbersDataProvider()
    {
        return array(
            'dynamic $_oldFieldsMap' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_DynamicWithNumbers',
            ),
            'static $_oldFieldsMap' => array(
                'Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_StaticWithNumbers',
            ),
        );
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
                'Zerkella_PhpMage_Varien_Object_Descendant_Invocation_Constructor_General',
                array(1, 2, 3),
                "_initOldFieldsMap()\n_prepareSyncFieldsMap()\n_addFullNames(): 1,2,3\n_construct(): 1,2,3\n"
            ),
            array(
                'Zerkella_PhpMage_Varien_Object_Descendant_Invocation_Constructor_WithoutOldFieldsMap',
                array(4, 5, 6),
                "_initOldFieldsMap()\n_addFullNames(): 4,5,6\n_construct(): 4,5,6\n"
            ),
        );
    }

    /**
     * Test, that non-array constructor param is ignored (i.e. nothing bad happens)
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testConstructorWrongArgument()
    {
        new Varien_Object('a');
    }
}
