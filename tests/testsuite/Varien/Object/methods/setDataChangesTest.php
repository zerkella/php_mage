<?php
class Varien_Object_methods_setDataChangesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $setValue
     * @param bool $expected
     * @dataProvider setDataChangesDataProvider
     */
    public function testSetDataChanges($setValue, $expected)
    {
        $object = new Varien_Object();
        $setResult = $object->setDataChanges($setValue);
        $this->assertSame($setResult, $object);
        $this->assertSame($object->hasDataChanges(), $expected);
    }

    /**
     * @return array
     */
    public static function setDataChangesDataProvider()
    {
        return array(
            'true boolean' => array(
                true,
                true,
            ),
            'true int' => array(
                22,
                true,
            ),
            'true string' => array(
                '22',
                true,
            ),
            'false boolean' => array(
                false,
                false,
            ),
            'false int' => array(
                0,
                false,
            ),
            'false string int' => array(
                '0',
                false,
            ),
            'false string empty' => array(
                '',
                false,
            ),
            'false null' => array(
                null,
                false,
            ),
        );
    }

    /**
     * Test that nothing bad happens, when the method is called without checking a return value
     */
    public function testSetDataChangesWithoutReturnValue()
    {
        $object = new Varien_Object();
        $object->setDataChanges(true);
    }
}
