<?php
class Varien_Object_methods_toArrayTest extends PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $object = $this->getMock('Varien_Object', array('__toArray'));
        $object->expects($this->once())
            ->method('__toArray')
            ->with(array('args'))
            ->will($this->returnValue('some_result'));

        $result = $object->toArray(array('args'));

        $this->assertEquals('some_result', $result);
    }

    public function testToArrayDefaultParameter()
    {
        $object = $this->getMock('Varien_Object', array('__toArray'));
        $object->expects($this->once())
            ->method('__toArray')
            ->with(array())
            ->will($this->returnValue('some_result'));

        $result = $object->toArray();

        $this->assertEquals('some_result', $result);
    }

    /**
     * @param $argument
     * @dataProvider toArrayWithNonArrayArgumentDataProvider
     */
    public function testToArrayWithNonArrayArgument($argument)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'must be an array');
        $object = new Varien_Object();
        $object->toArray($argument);
    }

    public static function toArrayWithNonArrayArgumentDataProvider()
    {
        return array(
            array(null),
            array('string'),
        );
    }
}
