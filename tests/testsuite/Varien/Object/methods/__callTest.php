<?php
class Varien_Object_methods___callTest extends PHPUnit_Framework_TestCase
{
    const RESULT_SAME_OBJECT = 172; // Compare result to the same object

    public static function setUpBeforeClass()
    {
        if (!class_exists('Varien_Exception')) {
            require_once(__DIR__ . '/_files/Varien_Exception.php');
        }
    }

    public function setUp()
    {
        // Clear caches
        $reflectionProperty = new ReflectionProperty('Varien_Object', '_underscoreCache');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(array());
    }

    /**
     * @param mixed $data
     * @param string $method
     * @param array $args
     * @param mixed $expectedResult
     * @param array $expectedData
     * @dataProvider __callDataProvider
     */
    public function test__call($data, $method, array $args, $expectedResult, array $expectedData)
    {
        $object = new Varien_Object($data);
        $result = call_user_func_array(array($object, $method), $args);
        if ($expectedResult === self::RESULT_SAME_OBJECT) {
            $expectedResult = $object;
        }
        $this->assertSame($expectedResult, $result);

        $actualData = $object->getData();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public static function __callDataProvider()
    {
        return array(
            'get with too many underscores2' => array(
                array('a_bc_de_fg_hi_jk_lm_no_pq_rs_tu_vw_xy_z' => '$15.00'), // Non-intuitive, but that's how it works
                'getABCDEFGHIJKLMNOPQRSTUVWXYZ',
                array(),
                '$15.00',
                array('a_bc_de_fg_hi_jk_lm_no_pq_rs_tu_vw_xy_z' => '$15.00'),
            ),
            'get' => array(
                array('final_product_price' => '$15.00'),
                'getFinalProductPrice',
                array(),
                '$15.00',
                array('final_product_price' => '$15.00'),
            ),
            'get without uppercases' => array(
                array('price' => '$15.00'),
                'getPrice',
                array(),
                '$15.00',
                array('price' => '$15.00'),
            ),
            'get with too many underscores' => array(
                array('a_bc_de_fg_hi_jk_lm_no_pq_rs_tu_vw_xy_z' => '$15.00'), // Non-intuitive, but that's how it works
                'getABCDEFGHIJKLMNOPQRSTUVWXYZ',
                array(),
                '$15.00',
                array('a_bc_de_fg_hi_jk_lm_no_pq_rs_tu_vw_xy_z' => '$15.00'),
            ),
            'get with numbers' => array(
                array('1' => '$15.00'),
                'get1',
                array(),
                '$15.00',
                array('1' => '$15.00'),
            ),
            'get with numbers and letters' => array(
                array('a_bcde1' => '$15.00'),
                'getABcde1',
                array(),
                '$15.00',
                array('a_bcde1' => '$15.00'),
            ),
            'get with method underscores' => array(
                array('a__b' => '$15.00'),
                'getA_B',
                array(),
                '$15.00',
                array('a__b' => '$15.00'),
            ),
            'get with lowercase at start' => array(
                array('something_new' => '$15.00'),
                'getsomethingNew',
                array(),
                '$15.00',
                array('something_new' => '$15.00'),
            ),
            'get with empty string' => array(
                array('something_new' => '$15.00'),
                'get',
                array(),
                array('something_new' => '$15.00'),
                array('something_new' => '$15.00'),
            ),
            'get with zero' => array(
                array('$15.00'),
                'get0',
                array(),
                '$15.00',
                array(0 => '$15.00'),
            ),
            'get with index' => array(
                array('arr' => array('a' => 'b', 'c' => 'd')),
                'getArr',
                array('c'),
                'd',
                array('arr' => array('a' => 'b', 'c' => 'd')),
            ),
            'get with empty index' => array(
                array('arr' => array('' => 'a')),
                'getArr',
                array(''),
                'a',
                array('arr' => array('' => 'a')),
            ),
            'set with existing data' => array(
                array('final_product_price' => '$15.00'),
                'setFinalProductPrice',
                array('$14.99'),
                self::RESULT_SAME_OBJECT,
                array('final_product_price' => '$14.99'),
            ),
            'set with non-existing data' => array(
                array('a' => 'b'),
                'setFinalProductPrice',
                array('$14.99'),
                self::RESULT_SAME_OBJECT,
                array('a' => 'b', 'final_product_price' => '$14.99'),
            ),
            'set with int existing data' => array(
                array(5 => '$15.00'),
                'set5',
                array('$14.99'),
                self::RESULT_SAME_OBJECT,
                array(5 => '$14.99'),
            ),
            'set with int non-existing data' => array(
                array(),
                'set5',
                array('$14.99'),
                self::RESULT_SAME_OBJECT,
                array(5 => '$14.99'),
            ),
            'set with non-zero int' => array(
                array(),
                'set100',
                array('$14.99'),
                self::RESULT_SAME_OBJECT,
                array(100 => '$14.99'),
            ),
            'set with implicit null' => array(
                array(),
                'setFinalPrice',
                array(),
                self::RESULT_SAME_OBJECT,
                array('final_price' => null),
            ),
            'set with explicit null' => array(
                array(),
                'setFinalPrice',
                array(null),
                self::RESULT_SAME_OBJECT,
                array('final_price' => null),
            ),
            'set without suffix' => array(
                array(),
                'set',
                array('a'),
                self::RESULT_SAME_OBJECT,
                array('' => 'a'),
            ),
            'set with 0' => array(
                array(),
                'set0',
                array('a'),
                self::RESULT_SAME_OBJECT,
                array(0 => 'a'),
            ),
            'unset with existing data' => array(
                array('final_product_price' => '$15.00', 'a' => 'b'),
                'unsFinalProductPrice',
                array(),
                self::RESULT_SAME_OBJECT,
                array('a' => 'b'),
            ),
            'unset with non-existing data' => array(
                array('a' => 'b'),
                'unsFinalProductPrice',
                array(),
                self::RESULT_SAME_OBJECT,
                array('a' => 'b'),
            ),
            'unset with int existing data' => array(
                array(12 => '$15.00', 'a' => 'b'),
                'uns12',
                array(),
                self::RESULT_SAME_OBJECT,
                array('a' => 'b'),
            ),
            'uns with int non-existing data' => array(
                array('a' => 'b'),
                'uns12',
                array(),
                self::RESULT_SAME_OBJECT,
                array('a' => 'b'),
            ),
            'unset without suffix' => array(
                array('a' => 'b', '' => 'c'),
                'uns',
                array(),
                self::RESULT_SAME_OBJECT,
                array('a' => 'b'),
            ),
            'has with existing data' => array(
                array('final_product_price' => '$15.00'),
                'hasFinalProductPrice',
                array(),
                true,
                array('final_product_price' => '$15.00'),
            ),
            'has with null data' => array(
                array('final_product_price' => null),
                'hasFinalProductPrice',
                array(),
                false,
                array('final_product_price' => null),
            ),
            'has with non-existing data' => array(
                array('a' => 'b'),
                'hasFinalProductPrice',
                array(),
                false,
                array('a' => 'b'),
            ),
            'has with int existing data' => array(
                array(12 => '$15.00', 'a' => 'b'),
                'has12',
                array(),
                true,
                array(12 => '$15.00', 'a' => 'b'),
            ),
            'has with int existing data, pointing to null' => array(
                array(12 => null, 'a' => 'b'),
                'has12',
                array(),
                false,
                array(12 => null, 'a' => 'b'),
            ),
            'has with int non-existing data' => array(
                array('a' => 'b'),
                'has12',
                array(),
                false,
                array('a' => 'b'),
            ),
        );
    }

    /**
     * Test, that when result is not checked, then everything works fine (no exceptions or segfaults)
     *
     * @param mixed $data
     * @param string $method
     * @param array $args
     * @param array $expectedData
     * @dataProvider __callMethodNoReturnDataProvider
     */
    public function test__callMethodNoReturn($data, $method, array $args, array $expectedData)
    {
        $object = new Varien_Object($data);
        call_user_func_array(array($object, $method), $args);

        $actualData = $object->getData();
        $this->assertSame($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public static function __callMethodNoReturnDataProvider()
    {
        return array(
            'get' => array(
                array('price' => 1),
                'getPrice',
                array(),
                array('price' => 1),
            ),
            'set' => array(
                array('price' => 1),
                'setPrice',
                array(2),
                array('price' => 2),
            ),
            'unset' => array(
                array('price' => 1, 'a' => 'b'),
                'unsPrice',
                array(),
                array('a' => 'b'),
            ),
            'has' => array(
                array('price' => 1, 'a' => 'b'),
                'hasPrice',
                array(),
                array('price' => 1, 'a' => 'b'),
            ),
        );
    }

    /**
     * Test, that the called method proxies core method
     *
     * @param string $method
     * @param array $args
     * @param string $expectedMethod
     * @param array $expectedArgs
     * @dataProvider __callProxyDataProvider
     */
    public function test__callProxy($method, array $args, $expectedMethod, array $expectedArgs)
    {
        $object = $this->getMock('Varien_Object', array($expectedMethod));
        $expectation = $object->expects($this->once())
            ->method($expectedMethod);
        $expectation = call_user_func_array(array($expectation, 'with'), $expectedArgs);
        $expectation->will($this->returnValue('some result'));

        $result = call_user_func_array(array($object, $method), $args);
        $this->assertEquals('some result', $result);
    }

    /**
     * @return array
     */
    public static function __callProxyDataProvider()
    {
        return array(
            'get without arguments' => array(
                'getFinalPrice',
                array(),
                'getData',
                array('final_price'),
            ),
            'get with arguments' => array(
                'getFinalPrice',
                array('sub_index'),
                'getData',
                array('final_price', 'sub_index'),
            ),
            'set without arguments' => array(
                'setFinalPrice',
                array(),
                'setData',
                array('final_price'),
            ),
            'set with arguments' => array(
                'setFinalPrice',
                array(10),
                'setData',
                array('final_price', 10),
            ),
            'unset' => array(
                'unsFinalPrice',
                array(),
                'unsetData',
                array('final_price'),
            ),
        );
    }

    /**
     * Test, that there are no segfaults, when exception is thrown in a proxied method
     *
     * @param string $method
     * @param array $args
     * @param string $proxiedMethod
     * @dataProvider __callSubExceptionDataProvider
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     */
    public function test__callSubException($method, array $args, $proxiedMethod)
    {
        $object = $this->getMock('Varien_Object', array($proxiedMethod));

        $e = new BadMethodCallException('some exception');
        $object->expects($this->once())
            ->method($proxiedMethod)
            ->will($this->throwException($e));

        $result = call_user_func_array(array($object, $method), $args);
    }

    /**
     * @return array
     */
    public static function __callSubExceptionDataProvider()
    {
        return array(
            'get without arguments' => array(
                'getFinalPrice',
                array(),
                'getData',
            ),
            'get with arguments' => array(
                'getFinalPrice',
                array('sub_index'),
                'getData',
            ),
            'set without arguments' => array(
                'setFinalPrice',
                array(),
                'setData',
            ),
            'set with arguments' => array(
                'setFinalPrice',
                array('$14.99'),
                'setData',
            ),
            'unset' => array(
                'unsFinalPrice',
                array(),
                'unsetData',
            ),
        );
    }

    /**
     * @param string $class
     * @param string $method
     * @dataProvider __callWrongMethodExceptionDataProvider
     */
    public function test__callWrongMethodException($class, $method)
    {
        $this->setExpectedException('Varien_Exception', "Invalid method {$class}::{$method}");
        $object = new $class();
        $object->$method();
    }

    /**
     * @return array
     */
    public static function __callWrongMethodExceptionDataProvider()
    {
        return array(
            'some unknown method to call' => array('Varien_Object', 'doSomething'),
            'small method name' => array('Varien_Object', 'aa'),
            'descendant class must use its name' => array('Zerkella_PhpMage_Varien_Object_Descendant_Dummy', 'aa'),
        );
    }
}
