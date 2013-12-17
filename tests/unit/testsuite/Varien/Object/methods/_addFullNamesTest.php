<?php
class Varien_Object_methods__addFullNamesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param array $expected
     * @dataProvider addFullNamesDataProvider
     */
    public function testAddFullNames(array $data, array $expected)
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames($data);
        $actual = $object->getData();

        /**
         * Order of elements differ in Varien_Object PHP implementation and C implementation,
         * however for Magento it doesn't make any difference. So make test order-insensitive
         */
        ksort($expected);
        if (is_array($actual)) {
            ksort($actual);
        }
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public static function addFullNamesDataProvider()
    {
        $data = array(
            'old_property1' => 'old',
            'new_property2' => 'new',
            'some_property' => 'some_value',
        );

        $dataWithReferences = $data;
        $dataWithReferences['old_property1'] = &$dataWithReferences['some_property'];

        $dataReferenced = $data;
        $dataReferenced['some_property'] = &$dataReferenced['old_property1'];

        $objectToSet = new StdClass;

        return array(
            'usual data' => array(
                'data' => $data,
                'expected' => array(
                    'old_property1' => 'old',
                    'new_property2' => 'new',
                    'some_property' => 'some_value',
                    'old_property2' => 'new',
                    'new_property1' => 'old',
                ),
            ),
            'data with references' => array(
                'data' => $dataWithReferences,
                'expected' => array(
                    'old_property1' => 'some_value',
                    'new_property2' => 'new',
                    'some_property' => 'some_value',
                    'old_property2' => 'new',
                    'new_property1' => 'some_value',
                ),
            ),
            'data referenced' => array(
                'data' => $dataReferenced,
                'expected' => array(
                    'old_property1' => 'old',
                    'new_property2' => 'new',
                    'some_property' => 'old',
                    'old_property2' => 'new',
                    'new_property1' => 'old',
                ),
            ),
            'data with object' => array(
                'data' => array(
                    'old_property1' => $objectToSet,
                ),
                'expected' => array(
                    'old_property1' => $objectToSet,
                    'new_property1' => $objectToSet,
                ),
            ),
        );
    }

    /**
     * Test for a case, when there are numbered old fields.
     * This use case is never used in Magento (moreover - it has bugs there), so the test just ensures, that
     * no exceptions or segfaults occur because of such call
     */
    public function testAddFullNamesWithNumbers()
    {
        $data = array(
            'old_property1' => 'old',
            'new_property2' => 'new',
            'some_property' => 'some_value',
            111 => 99,
        );
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNamesWithNumbers($data);
    }

    public function testAddFullNamesWithReferenceChanges()
    {
        $oldProperty = 'a';
        $data = array('old_property1' => &$oldProperty);
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames($data);

        // Change referenced value
        $oldProperty = 'b';

        // Verify, that referenced values (and only them) have changed inside the object
        $actual = $object->getData();
        $expected = array(
            'old_property1' => 'b',
            'new_property1' => 'a',
        );
        $this->assertEquals($expected, $actual);
    }

    public function testAddFullNamesNotLinkedToInitialData()
    {
        $data = array('old_property1' => 'old_property1_value');
        new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames($data);
        $this->assertEquals(array('old_property1' => 'old_property1_value'), $data);
    }
}
