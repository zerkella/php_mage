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
}
