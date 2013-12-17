<?php
class Varien_Object_methods_toJsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider toJsonProxiesProtectedToJsonDataProvider
     */
    public function testToJsonProxiesProtectedToJson($args, $expectedArgs)
    {
        $object = $this->getMock('Varien_Object', array('__toJson'));
        $mocker = $object->expects($this->once())
            ->method('__toJson');
        $mocker = call_user_func_array(array($mocker, 'with'), $expectedArgs);
        $mocker->will($this->returnValue('result from __toJson()'));

        $result = call_user_func_array(array($object, 'toJson'), $args);
        $this->assertEquals($result, 'result from __toJson()');
    }

    /**
     * @return array
     */
    public static function toJsonProxiesProtectedToJsonDataProvider() {
        return array(
            '0 params' => array(
                array(),
                array(array()),
            ),
            '1 param' => array(
                array(array('a')),
                array(array('a')),
            ),
        );
    }

    /**
     * @param mixed $arrAttributes
     * @dataProvider toJsonWithNonArrayArgumentDataProvider
     */
    public function test__toJsonWithNonArrayArgument($arrAttributes)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'array');
        $object = new Varien_Object();
        $object->toJson($arrAttributes);
    }

    /**
     * @return array
     */
    public static function toJsonWithNonArrayArgumentDataProvider()
    {
        return array(
            array(null),
            array('string'),
        );
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     */
    public function testToJsonSubException()
    {
        $object = $this->getMock('Varien_Object', array('__toJson'));
        $object->expects($this->once())
            ->method('__toJson')
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->toJson();
    }
}
