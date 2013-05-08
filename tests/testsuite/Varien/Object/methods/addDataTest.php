<?php
class Varien_Object_methods_addDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $initialData
     * @param array $dataToPass
     * @param mixed $expectedData
     * @dataProvider addDataDataProvider
     */
    public function testAddData($initialData, $dataToPass, $expectedData)
    {
        $object = new Varien_Object($initialData);
        $object->addData($dataToPass);
        $actualData = $object->getData();
        $this->assertEquals($expectedData, $actualData);
    }

    public static function addDataDataProvider()
    {
        return array(
            'usual data' => array(
                array('a' => 'b', 5 => 6),
                array('a' => 'c', 7 => 8),
                array('a' => 'c', 5 => 6, 7 => 8),
            ),
            'empty data' => array(
                array('a' => 'b', 5 => 6),
                array(),
                array('a' => 'b', 5 => 6),
            ),
        );
    }

    public function testAddDataReturnsSelf()
    {
        $object = new Varien_Object();
        $result = $object->addData(array());
        $this->assertSame($object, $result);
    }
}
