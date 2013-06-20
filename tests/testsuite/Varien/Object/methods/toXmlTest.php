<?php
class Varien_Object_methods_toXmlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider toXmlProxiesProtectedToXmlDataProvider
     */
    public function testToXmlProxiesProtectedToXml($args, $expectedArgs)
    {
        $object = $this->getMock('Varien_Object', array('__toXml'));
        $mocker = $object->expects($this->once())
            ->method('__toXml');
        $mocker = call_user_func_array(array($mocker, 'with'), $expectedArgs);
        $mocker->will($this->returnValue('result from __toXml()'));

        $result = call_user_func_array(array($object, 'toXml'), $args);
        $this->assertEquals($result, 'result from __toXml()');
    }

    /**
     * @return array
     */
    public static function toXmlProxiesProtectedToXmlDataProvider() {
        return array(
            '0 params' => array(
                array(),
                array(array(), 'item', false, true),
            ),
            '1 param' => array(
                array(array('a')),
                array(array('a'), 'item', false, true),
            ),
            '2 params' => array(
                array(array('a'), 'rootName'),
                array(array('a'), 'rootName', false, true),
            ),
            '3 params' => array(
                array(array('a'), 'rootName', true),
                array(array('a'), 'rootName', true, true),
            ),
            '4 params' => array(
                array(array('a'), 'rootName', true, false),
                array(array('a'), 'rootName', true, false),
            ),
        );
    }

    /**
     * @param mixed $arrAttributes
     * @dataProvider toXmlWithNonArrayArgumentDataProvider
     */
    public function test__toXmlWithNonArrayArgument($arrAttributes)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'array');
        $object = new Varien_Object();
        $object->toXml($arrAttributes);
    }

    /**
     * @return array
     */
    public static function toXmlWithNonArrayArgumentDataProvider()
    {
        return array(
            array(null),
            array('string'),
        );
    }
}
