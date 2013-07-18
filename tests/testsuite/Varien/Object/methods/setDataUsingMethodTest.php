<?php
class Varien_Object_methods_setDataUsingMethodTest extends PHPUnit_Framework_TestCase
{
    public function testSetDataUsingMethodReturnsSelf()
    {
        $object = $this->getMock('Varien_Object', array('setA'));
        $this->assertSame($object, $object->setDataUsingMethod('a', 'b'));
    }

    /**
     * @param mixed $key
     * @param array $args
     * @param string $expectedMethod
     * @dataProvider setDataUsingMethodDataProvider
     */
    public function testSetDataUsingMethod($key, $args, $expectedMethod)
    {
        $expectedArgs = unserialize(serialize($args)); // To break link with $args
        $object = $this->getMock('Varien_Object', array($expectedMethod));
        $object->expects($this->once())
            ->method($expectedMethod)
            ->with($expectedArgs);

        $object->setDataUsingMethod($key, $args);
    }

    /**
     * @return array
     */
    public static function setDataUsingMethodDataProvider()
    {
        return array(
            'usual method' => array(
                'final_price',
                array(1, 2),
                'setFinalPrice'
            ),
            'many underscores' => array(
                '__final__price_',
                array(1, 2),
                'setFinalPrice'
            ),
            'numbers in method name' => array(
                'is_windows_64',
                array(1, 2),
                'setIsWindows64'
            ),
            'numbers in method name together with string' => array(
                'is_windows64',
                array(1, 2),
                'setIsWindows64'
            ),
            'int key' => array(
                '1',
                array(1, 2),
                'set1'
            ),
            'non-array args' => array(
                'final_price',
                'some_string',
                'setFinalPrice'
            ),
            'uppercase method name' => array(
                'Final_Price',
                'some_string',
                'setFinalPrice'
            ),
            'uppercase method name without underscores' => array(
                'Final_PriceData',
                'some_string',
                'setFinalPriceData'
            ),
        );
    }

    public function testSetDataUsingMethodWithoutArgs()
    {
        $object = $this->getMock('Varien_Object', array('setSomething'));
        $object->expects($this->once())
            ->method('setSomething')
            ->with(array());

        $object->setDataUsingMethod('something');
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @param string $key
     * @param string $expectedMethod
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     * @dataProvider setDataUsingMethodSubExceptionDataProvider
     */
    public function testSetDataUsingMethodSubException($key, $expectedMethod)
    {
        $object = $this->getMock('Varien_Object', array($expectedMethod));
        $object->expects($this->once())
            ->method($expectedMethod)
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->setDataUsingMethod($key, 1);
    }

    /**
     * @return array
     */
    public static function setDataUsingMethodSubExceptionDataProvider()
    {
        return array(
            'usual method' => array('anything', 'setAnything'),
            'via magic method' => array('anything', 'setData'),
        );
    }

    public function testSetDataUsingMethodThroughMagicCallWithoutGettingResult()
    {
        $object = new Varien_Object(array('final_price' => '$15.00'));
        $object->setDataUsingMethod('final_price', '$30.00');
        $this->assertEquals('$30.00', $object->getData('final_price'));
    }
}
