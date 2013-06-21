<?php
class Varien_Object_methods___toJsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ReflectionMethod
     */
    protected $_method;

    public static function setUpBeforeClass()
    {
        require_once __DIR__ . '/_files/Zend_Json.php';
    }

    public function setUp()
    {
        Zend_Json::setCallback(null); // Clear, so we don't have unexpected behaviour
        $this->_method = new ReflectionMethod('Varien_Object', '__toJson');
        $this->_method->setAccessible(true);
    }

    public function tearDown()
    {
        Zend_Json::setCallback(null); // Clear, so we don't have unexpected behaviour
    }

    /**
     * @param array $data
     * @param mixed $expectedZendJsonArg
     * @param mixed $arrAttributes
     * @dataProvider __toJsonDataProvider
     */
    public function test__toJson($data, $expectedZendJsonArg, $arrAttributes = 'NOTSET')
    {
        $mock = $this->getMock('StdClass', array('onJsonEncode'));
        $mock->expects($this->once())
            ->method('onJsonEncode')
            ->with($expectedZendJsonArg)
            ->will($this->returnValue('result from Zend_Json::encode()'));

        Zend_Json::setCallback(array($mock, 'onJsonEncode'));

        // Compose list of arguments to pass
        $args = array();
        if ($arrAttributes != 'NOTSET') {
            $args[] = $arrAttributes;
        }

        // Invoke and tests
        $object = new Varien_Object($data);
        $result = $this->_method->invokeArgs($object, $args);

        $this->assertEquals('result from Zend_Json::encode()', $result);
    }

    /**
     * @return array
     */
    public static function __toJsonDataProvider()
    {
        return array(
            'nothing' => array(
                'data' => array(),
                'expectedZendJsonArg' => array(),
            ),
            'array of data to encode' => array(
                'data' => array('a' => 'b', 1 => 2),
                'expectedZendJsonArg' => array('a' => 'b', 1 => 2),
            ),
            'array of data to encode with custom param' => array(
                'data' => array('a' => 'b', 1 => 2),
                'expectedZendJsonArg' => array('a' => 'b', 1 => 2),
                array(),
            ),
            'filtered array of data' => array(
                'data' => array('a' => 'b', 1 => 2),
                'expectedZendJsonArg' => array('a' => 'b', 'c' => null),
                array('a', 'c'),
            ),
        );
    }

    /**
     * @param mixed $arrAttributes
     * @dataProvider __toJsonWithNonArrayArgumentDataProvider
     */
    public function test__toJsonWithNonArrayArgument($arrAttributes)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'array');
        $object = new Varien_Object();

        $this->_method->invoke($object, $arrAttributes);
    }

    /**
     * @return array
     */
    public static function __toJsonWithNonArrayArgumentDataProvider()
    {
        return array(
            array(null),
            array('string'),
        );
    }
}
