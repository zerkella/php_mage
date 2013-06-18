<?php
class Varien_Object_methods___toXmlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ReflectionMethod
     */
    protected $_method;

    public function setUp()
    {
        $this->_method = new ReflectionMethod('Varien_Object', '__toXml');
        $this->_method->setAccessible(true);
    }

    /**
     * @param array $data
     * @param string $expectedResult
     * @param mixed $arrAttributes
     * @param mixed $rootName
     * @param mixed $addOpenTag
     * @param mixed $addCdata
     * @dataProvider __toXmlDataProvider
     */
    public function test__toXml($data, $expectedResult, $arrAttributes = 'NOTSET', $rootName = 'NOTSET',
        $addOpenTag = 'NOTSET', $addCdata = 'NOTSET')
    {
        // Compose list of arguments to pass
        $args = array();
        foreach (array($arrAttributes, $rootName, $addOpenTag, $addCdata) as $checkParam) {
            if ($checkParam === 'NOTSET') {
                break;
            }
            $args[] = $checkParam;
        }

        // Invoke and tests
        $object = new Varien_Object($data);
        $result = $this->_method->invokeArgs($object, $args);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public static function __toXmlDataProvider()
    {
        return array(
            'nothing' => array(
                'data' => array(),
                'expectedResult' => '',
                'arrAttributes' => array(),
                'rootName' => null,
                'addOpenTag' => false,
                'addCdata' => false,
            ),
            'xml-header' => array(
                'data' => array(),
                'expectedResult' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n",
                'arrAttributes' => array(),
                'rootName' => '',
                'addOpenTag' => true,
            ),
            'xml-header with non-bool true value' => array(
                'data' => array(),
                'expectedResult' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n",
                'arrAttributes' => array(),
                'rootName' => '',
                'addOpenTag' => 'a',
            ),
            'default root element' => array(
                'data' => array(),
                'expectedResult' => "<item>\n</item>\n",
            ),
            'empty string root element' => array(
                'data' => array(),
                'expectedResult' => '',
                'arrAttributes' => array(),
                'rootName' => '',
            ),
            'null root element' => array(
                'data' => array(),
                'expectedResult' => '',
                'arrAttributes' => array(),
                'rootName' => null,
            ),
            'custom root element' => array(
                'data' => array(),
                'expectedResult' => "<kolgosp>\n</kolgosp>\n",
                'arrAttributes' => array(),
                'rootName' => 'kolgosp',
            ),
            'string field, string value' => array(
                'data' => array('a' => 'b'),
                'expectedResult' => "<a>b</a>\n",
                'arrAttributes' => array('a'),
                'rootName' => null,
            ),
            'string field, object value' => array(
                'data' => array('a' => new SplFileInfo('b')),
                'expectedResult' => "<a>b</a>\n",
                'arrAttributes' => array('a'),
                'rootName' => null,
            ),
            'int field, int value' => array(
                'data' => array(1 => 2),
                'expectedResult' => "<1>2</1>\n",
                'arrAttributes' => array(1),
                'rootName' => null,
            ),
            'attribute filter' => array(
                'data' => array('a' => 'b', 1 => 2, 'c' => 'd', 3 => 4),
                'expectedResult' => "<3>4</3>\n<a>b</a>\n",
                'arrAttributes' => array('3', 'a'),
                'rootName' => null,
            ),
            'attribute empty filter must render everything' => array(
                'data' => array('a' => 'b', 1 => 2),
                'expectedResult' => "<a>b</a>\n<1>2</1>\n",
                'arrAttributes' => array(),
                'rootName' => null,
            ),
            'attribute filter with non-existing values' => array(
                'data' => array(),
                'expectedResult' => "<a></a>\n<1></1>\n",
                'arrAttributes' => array('a', 1),
                'rootName' => null,
            ),
            'cdata used' => array(
                'data' => array('a' => 'b'),
                'expectedResult' => "<a><![CDATA[b]]></a>\n",
                'arrAttributes' => array('a'),
                'rootName' => null,
                'addOpenTag' => false,
                'addCdata' => true,
            ),
            'cdata not with non-bool true value' => array(
                'data' => array('a' => 'b'),
                'expectedResult' => "<a>b</a>\n",
                'arrAttributes' => array('a'),
                'rootName' => null,
                'addOpenTag' => false,
                'addCdata' => 1,
            ),
            'html entities' => array(
                'data' => array('a' => '-&-"-\'-<->-'),
                'expectedResult' => "<a>-&amp;-&quot;-&apos;-&lt;-&gt;-</a>\n",
                'arrAttributes' => array('a'),
                'rootName' => null,
            ),
            'default values' => array(
                'data' => array('a' => 'b', 1 => 2),
                'expectedResult' => "<item>\n<a>b</a>\n<1>2</1>\n</item>\n",
            ),
            'xml-snippet' => array(
                'data' => array('price' => 0.99, 'name' => 'M&Ms', 'description' => 'none'),
                'expectedResult' => '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                    . "<product>\n"
                    . "<name><![CDATA[M&amp;Ms]]></name>\n"
                    . "<price><![CDATA[0.99]]></price>\n"
                    . "<sku><![CDATA[]]></sku>\n"
                    . "</product>\n",
                'arrAttributes' => array('name', 'price', 'sku'),
                'rootName' => 'product',
                'addOpenTag' => true,
                'addCdata' => true,
            ),
        );
    }

    /**
     * @param mixed $arrAttributes
     * @dataProvider __toXmlWithNonArrayArgumentDataProvider
     */
    public function test__toXmlWithNonArrayArgument($arrAttributes)
    {
        $this->setExpectedException('PHPUnit_Framework_Error', 'array');
        $object = new Varien_Object();

        $this->_method->invoke($object, $arrAttributes);
    }

    /**
     * @return array
     */
    public static function __toXmlWithNonArrayArgumentDataProvider()
    {
        return array(
            array(null),
            array('string'),
        );
    }
}
