<?php
class Varien_Object_methods___setTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param mixed $data
     * @param string $property
     * @param mixed $value
     * @param mixed $expectedData
     * @dataProvider __setDataProvider
     */
    public function test__set($data, $property, $value, $expectedData)
    {
        $object = new Varien_Object($data);
        $object->$property = $value;
        $this->assertSame($expectedData, $object->getData());
    }

    /**
     * @return array
     */
    public static function __setDataProvider()
    {
        $object = new stdClass;
        return array(
            'non-existing property' => array(
                array(),
                'finalProductPrice',
                '$15.00',
                array('final_product_price' => '$15.00'),
            ),
            'existing property' => array(
                array('final_product_price' => '$15.00'),
                'finalProductPrice',
                '$10.00',
                array('final_product_price' => '$10.00'),
            ),
            'full camelized property' => array(
                array(),
                'FinalProductPrice',
                '$15.00',
                array('final_product_price' => '$15.00'),
            ),
            'non-camelized property' => array(
                array(),
                'final_product_price',
                '$15.00',
                array('final_product_price' => '$15.00'),
            ),
            'object value' => array(
                array(),
                'price',
                $object,
                array('price' => $object),
            ),
            'int property' => array(
                array(),
                '5',
                8,
                array(5 => 8),
            ),
        );
    }

    /**
     * Test, that __set() method proxies setData()
     */
    public function test__setProxy()
    {
        $object = $this->getMock('Varien_Object', array('setData'));
        $object->expects($this->once())
            ->method('setData')
            ->with('final_product_price', 10);

        $object->finalProductPrice = 10;
    }

    /**
     * Test, that there are no segfaults, when exception is thrown in the proxied method
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     */
    public function test__getSubException()
    {
        $object = $this->getMock('Varien_Object', array('setData'));

        $e = new BadMethodCallException('some exception');
        $object->expects($this->once())
            ->method('setData')
            ->will($this->throwException($e));

        $object->someProperty = 10;
    }
}
