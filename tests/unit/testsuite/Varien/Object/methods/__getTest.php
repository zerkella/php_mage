<?php
class Varien_Object_methods___getTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $data
     * @param string $property
     * @param mixed $expectedResult
     * @dataProvider __getDataProvider
     */
    public function test__get($data, $property, $expectedResult)
    {
        $object = new Varien_Object($data);
        $result = $object->$property;
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public static function __getDataProvider()
    {
        $object = new stdClass;
        return array(
            'usual property' => array(
                array('final_product_price' => '$15.00'),
                'finalProductPrice',
                '$15.00',
            ),
            'full camelized property' => array(
                array('final_product_price' => '$15.00'),
                'FinalProductPrice',
                '$15.00',
            ),
            'non-camelized property' => array(
                array('final_product_price' => '$15.00'),
                'final_product_price',
                '$15.00',
            ),
            'non-existing property' => array(
                array(),
                'final_product_price',
                null,
            ),
            'object result' => array(
                array('price' => $object),
                'price',
                $object,
            ),
            'int property' => array(
                array(5 => 8),
                '5',
                8,
            ),
        );
    }

    /**
     * Test, that when result is not checked, then everything works fine (no exceptions or segfaults)
     */
    public function test__getNoReturnProperty()
    {
        $object = new Varien_Object(array('a' => 'b'));
        $object->__a;
    }

    /**
     * Test, that when result is not checked, then everything works fine (no exceptions or segfaults)
     * Test __get() call directly
     */
    public function test__getNoReturnPropertyDirectCall()
    {
        $object = new Varien_Object(array('a' => 'b'));
        $object->__get('a');
    }

    /**
     * Test direct call with NULL passed
     */
    public function test__getWithNullPassed()
    {
        $object = new Varien_Object(array('a' => 'b'));
        $result = $object->__get(null);
        $this->assertEquals(array('a' => 'b'), $result);
    }

    /**
     * Test, that __get() method proxies getData()
     */
    public function test__getProxy()
    {
        $object = $this->getMock('Varien_Object', array('getData'));
        $object->expects($this->once())
            ->method('getData')
            ->with('final_product_price')
            ->will($this->returnValue('a'));

        $result = $object->finalProductPrice;
        $this->assertEquals('a', $result);
    }

    /**
     * Test, that there are no segfaults, when exception is thrown in the proxied method
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     */
    public function test__getSubException()
    {
        $object = $this->getMock('Varien_Object', array('getData'));

        $e = new BadMethodCallException('some exception');
        $object->expects($this->once())
            ->method('getData')
            ->will($this->throwException($e));

        $object->someProperty;
    }
}
