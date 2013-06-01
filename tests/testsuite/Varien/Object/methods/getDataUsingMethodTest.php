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
            ->with(array())
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
}
