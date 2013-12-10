<?php
class Varien_Object_methods_getSetOrigDataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSetGetOrigDataDataProvider
     */
    public function testSetGetOrigData($setArgs, $getArgs, $expectedGetResult)
    {
        $object = new Varien_Object();

        $result = call_user_func_array(array($object, 'setOrigData'), $setArgs);
        $this->assertSame($object, $result);

        $result = call_user_func_array(array($object, 'getOrigData'), $getArgs);
        $this->assertSame($expectedGetResult, $result);
    }

    /**
     * @return array
     */
    public static function getSetGetOrigDataDataProvider() {
        $testObject = new StdClass;
        return array(
            'set by string key, get without params' => array(
                array('key', $testObject),
                array(),
                array('key' => $testObject),
            ),
            'set by int key, get without params' => array(
                array(2, $testObject),
                array(),
                array(2 => $testObject),
            ),
            'set by string int key, get without params' => array(
                array('2', $testObject),
                array(),
                array(2 => $testObject),
            ),
            'set by string key, get by string key' => array(
                array('key', $testObject),
                array('key'),
                $testObject,
            ),
            'set by int key, get by int key' => array(
                array(2, $testObject),
                array(2),
                $testObject,
            ),
            'set by string int key, get by int key' => array(
                array('2', $testObject),
                array(2),
                $testObject,
            ),
            'set by string int key, get by string int key' => array(
                array('2', $testObject),
                array('2'),
                $testObject,
            ),
            'set by int key, get by string int key' => array(
                array(2, $testObject),
                array('2'),
                $testObject,
            ),
            'set by empty string key, get by empty string key' => array(
                array('', $testObject),
                array(''),
                $testObject,
            ),
            'retrive non-existing key' => array(
                array('a', $testObject),
                array('b'),
                null,
            ),
        );
    }

    public function testSetOrigDataWithoutParams()
    {
        $data = array('a' => new StdClass);
        $object = new Varien_Object($data);

        $object->setOrigData();
        $actualData = $object->getOrigData();
        $this->assertSame($data, $actualData);
    }

    public function testSetOrigDataByNull()
    {
        $testObject = new StdClass;
        $data = array('a' => $testObject);
        $object = new Varien_Object($data);

        $object->setOrigData(null);

        $actualData = $object->getOrigData();
        $this->assertSame($data, $actualData);

        $actualValue = $object->getOrigData('a');
        $this->assertSame($testObject, $actualValue);
    }

    public function testGetOrigDataByNull()
    {
        $data = array('a' => new StdClass);
        $object = new Varien_Object($data);

        $object->setOrigData(null);
        $actualData = $object->getOrigData(null);
        $this->assertSame($data, $actualData);
    }

    public function testGetOrigDataByDefault()
    {
        $object = new Varien_Object();
        $origData = $object->getOrigData();
        $this->assertNull($origData);
    }

    public function testGetOrigDataDefaultByKey()
    {
        $object = new Varien_Object();
        $origDataByKey = $object->getOrigData('key');
        $this->assertNull($origDataByKey);
    }

    /**
     * Test that nothing bad happens, when setOrigData and getOrigData() are called without checking a return value
     */
    public function testSetGetOrigDataWithoutReturnValue()
    {
        $object = new Varien_Object();
        $object->setOrigData();
        $object->getOrigData();
    }

    public function testSetOrigDataNoLinkFromData()
    {
        $object = new Varien_Object(array('a' => 'b'));
        $object->setOrigData();
        $object->setData('c', 'd');
        $origData = $object->getOrigData();
        $this->assertSame(array('a' => 'b'), $origData);
    }

    public function testSetOrigDataNoLinkFromDataByRef()
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_DataByRef();
        $data = array('a' => 'b');
        $object->setDataByRef($data);
        $object->setOrigData();
        $object->setData('c', 'd');
        $origData = $object->getOrigData();
        $this->assertSame(array('a' => 'b'), $origData);
    }

    public function testSetOrigDataNoLinkToData()
    {
        $object = new Varien_Object(array('a' => 'b'));
        $object->setOrigData();
        $object->setOrigData('c', 'd');
        $data = $object->getData();
        $this->assertSame(array('a' => 'b'), $data);
    }

}
