<?php
class Varien_Object_methods_getDataUsingMethodTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $key
     * @param array $args
     * @param string $expectedMethod
     * @dataProvider getDataUsingMethodDataProvider
     */
    public function testGetDataUsingMethod($key, $args, $expectedMethod)
    {
        // Compose expected values, but do it without any possible reference links among them
        $expectedArgs = unserialize(serialize($args));
        $returnedResult = 'some_result';
        $expectedResult = 'some_result';

        // Test
        $object = $this->getMock('Varien_Object', array($expectedMethod));
        $object->expects($this->once())
            ->method($expectedMethod)
            ->with($expectedArgs)
            ->will($this->returnValue($returnedResult));

        $actualResult = $object->getDataUsingMethod($key, $args);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array
     */
    public static function getDataUsingMethodDataProvider()
    {
        return array(
            'usual method' => array(
                'final_price',
                array(1, 2),
                'getFinalPrice'
            ),
            'many underscores' => array(
                '__final__price_',
                array(1, 2),
                'getFinalPrice'
            ),
            'numbers in method name' => array(
                'is_windows_64',
                array(1, 2),
                'getIsWindows64'
            ),
            'numbers in method name together with string' => array(
                'is_windows64',
                array(1, 2),
                'getIsWindows64'
            ),
            'int key' => array(
                '1',
                array(1, 2),
                'get1'
            ),
            'non-array args' => array(
                'final_price',
                'some_string',
                'getFinalPrice'
            ),
            'uppercase method name' => array(
                'Final_Price',
                'some_string',
                'getFinalPrice'
            ),
            'uppercase method name without underscores' => array(
                'Final_PriceData',
                'some_string',
                'getFinalPriceData'
            ),
        );
    }

    public function testGetDataUsingMethodWithoutArgs()
    {
        // Compose expected values, but do it without any possible reference links among them
        $returnedResult = 'some_result';
        $expectedResult = 'some_result';

        $object = $this->getMock('Varien_Object', array('getSomething'));
        $object->expects($this->once())
            ->method('getSomething')
            ->with(null)
            ->will($this->returnValue($returnedResult));

        $actualResult = $object->getDataUsingMethod('something');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Test running method without observing returned value - it must run without any internal php issues
     */
    public function testGetDataUsingMethodNoReturnedValue()
    {
        $object = new Varien_Object();
        $object->getDataUsingMethod('data', 'a');
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @param string $key
     * @param string $expectedMethod
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     * @dataProvider getDataUsingMethodSubExceptionDataProvider
     */
    public function testGetDataUsingMethodSubException($key, $expectedMethod)
    {
        $object = $this->getMock('Varien_Object', array($expectedMethod));
        $object->expects($this->once())
            ->method($expectedMethod)
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->getDataUsingMethod($key);
    }

    /**
     * @return array
     */
    public static function getDataUsingMethodSubExceptionDataProvider()
    {
        return array(
            'usual method' => array('anything', 'getAnything'),
            'via magic method' => array('anything', 'getData'),
        );
    }


    public function testGetDataUsingMethodThroughMagicCall()
    {
        $object = new Varien_Object(array('final_price' => '$15.00'));
        $result = $object->getDataUsingMethod('final_price');
        $this->assertEquals('$15.00', $result);
    }
}
