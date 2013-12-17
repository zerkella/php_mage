<?php
/**
 * Test whether cloning goes right
 */
class Varien_Object_cloneTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Varien_Object
     */
    protected $_originalObject;

    /**
     * @var Varien_Object
     */
    protected $_clonedObject;

    public function setUp()
    {
        $this->_originalObject = new Varien_Object(array('a' => 'b', 'c' => 'd'));
        $this->_clonedObject = clone $this->_originalObject;
    }

    public function testDataInObjects()
    {
        $objSerialize = serialize($this->_originalObject);
        $obj2Serialize = serialize($this->_clonedObject);
        $this->assertEquals($objSerialize, $obj2Serialize);
    }

    public function testNoLinkFromOriginalToCloned()
    {
        $this->_originalObject->setData('e', 'f');
        $this->assertEquals(array('a' => 'b', 'c' => 'd'), $this->_clonedObject->getData());
    }

    public function testNoLinkFromClonedToOriginal()
    {
        $this->_clonedObject->setData('e', 'f');
        $this->assertEquals(array('a' => 'b', 'c' => 'd'), $this->_originalObject->getData());
    }
}
