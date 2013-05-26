<?php
class Varien_Object_methods__addFullNamesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param array $expected
     * @dataProvider addFullNamesDataProvider
     */
    public function testAddFullNames($data, $expected)
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames($data);
        $actual = $object->getData();
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
            111 => 99,
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
                    111 => 99,
                    'new_property1' => 'old',
                    333 => 99,
                    'old_property2' => 'new',
                ),
            ),
            'data with references' => array(
                'data' => $dataWithReferences,
                'expected' => array(
                    'old_property1' => 'some_value',
                    'new_property2' => 'new',
                    'some_property' => 'some_value',
                    111 => 99,
                    'new_property1' => 'some_value',
                    333 => 99,
                    'old_property2' => 'new',
                ),
            ),
            'data referenced' => array(
                'data' => $dataReferenced,
                'expected' => array(
                    'old_property1' => 'old',
                    'new_property2' => 'new',
                    'some_property' => 'old',
                    111 => 99,
                    'new_property1' => 'old',
                    333 => 99,
                    'old_property2' => 'new',
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

    public function testAddFullNamesWithReferenceChanges()
    {
        $oldProperty = 'a';
        $oldNumProperty = 1;
        $data = array('old_property1' => &$oldProperty, 111 => &$oldNumProperty);
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames($data);

        // Change referenced values
        $oldProperty = 'b';
        $oldNumProperty = 2;

        // Verify, that referenced values (and only them) have changed inside the object
        $actual = $object->getData();
        $expected = array(
            'old_property1' => 'b',
            'new_property1' => 'a',
            111 => 2,
            333 => 1,
        );
        $this->assertEquals($expected, $actual);
    }

    public function testAddFullNamesNotLinkedToInitialData()
    {
        $data = array('old_property1' => 'old_property1_value', 111 => '111_value');
        new Zerkella_PhpMage_Varien_Object_Descendant_AddFullNames($data);
        $this->assertEquals(array('old_property1' => 'old_property1_value', 111 => '111_value'), $data);
    }
}
