<?php
class Varien_Object_methods_toStringTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param array $args
     * @param string $expectedResult
     * @dataProvider toStringDataProvider
     */
    public function testToString(array $data, array $args, $expectedResult)
    {
        $object = new Varien_Object($data);
        $result = call_user_func_array(array($object, 'toString'), $args);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public static function toStringDataProvider()
    {
        return array(
            'no format, so cvs is returned' => array(
                'data' => array('a' => 'b', 1 => 2, new SplFileInfo('file')),
                'args' => array(),
                'expectedResult' => 'b, 2, file',
            ),
            'empty string format, so cvs is returned' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array(''),
                'expectedResult' => 'b, 2',
            ),
            'int zero format, so cvs is returned' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array(0),
                'expectedResult' => 'b, 2',
            ),
            'bool false format, so cvs is returned' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array(false),
                'expectedResult' => 'b, 2',
            ),
            'null format, so cvs is returned' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array(false),
                'expectedResult' => 'b, 2',
            ),
            'small format, so it is just returned' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array('a'),
                'expectedResult' => 'a',
            ),
            'wrong empty placeholder, so it is just returned' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array('{{}}'),
                'expectedResult' => '{{}}',
            ),
            'no placeholders' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array('A baba galamaga'),
                'expectedResult' => 'A baba galamaga',
            ),
            'empty placeholder inside' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array('111{{}}222'),
                'expectedResult' => '111{{}}222',
            ),
            'string placeholder' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array('111{{a}}222'),
                'expectedResult' => '111b222',
            ),
            'int placeholder' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array('aaa{{1}}bbb'),
                'expectedResult' => 'aaa2bbb',
            ),
            'placeholder for converted object' => array(
                'data' => array('object' => new SplFileInfo('file')),
                'args' => array('aaa{{object}}bbb'),
                'expectedResult' => 'aaafilebbb',
            ),
            'multiple placeholders' => array(
                'data' => array('a' => 'b', 1 => 2),
                'args' => array('-{{a}}- -{{1}}- -{{a}}-'),
                'expectedResult' => '-b- -2- -b-',
            ),
            'minimal placeholder' => array(
                'data' => array('a' => 'b'),
                'args' => array('{{a}}'),
                'expectedResult' => 'b',
            ),
            'placeholder at start' => array(
                'data' => array('a' => 'b'),
                'args' => array('{{a}} 111'),
                'expectedResult' => 'b 111',
            ),
            'placeholder at end' => array(
                'data' => array('a' => 'b'),
                'args' => array('111 {{a}}'),
                'expectedResult' => '111 b',
            ),
            'placeholder with underscore' => array(
                'data' => array('o_o' => '^_^'),
                'args' => array('{{o_o}}'),
                'expectedResult' => '^_^',
            ),
            'placeholder with capital letters' => array(
                'data' => array('FollowTheWhiteRabbit' => '-->'),
                'args' => array('{{FollowTheWhiteRabbit}}'),
                'expectedResult' => '-->',
            ),
            'converted format' => array(
                'data' => array('file' => 'name.txt'),
                'args' => array(new SplFileInfo('look at {{file}}')),
                'expectedResult' => 'look at name.txt',
            ),
            'non-existing placeholder' => array(
                'data' => array('a' => 'b'),
                'args' => array('11 {{c}} 11'),
                'expectedResult' => '11  11',
            ),
            'not placeholder - single start brace' => array(
                'data' => array('a' => 'b'),
                'args' => array('111 {a}} 111'),
                'expectedResult' => '111 {a}} 111',
            ),
            'not placeholder - single end brace' => array(
                'data' => array('a' => 'b'),
                'args' => array('111 {{a} 111'),
                'expectedResult' => '111 {{a} 111',
            ),
            'not placeholder - bad symbol' => array(
                'data' => array('$a' => 'b'),
                'args' => array('111 {{$a}} 111'),
                'expectedResult' => '111 {{$a}} 111',
            ),
            'bad placeholder does not influence other placeholder' => array(
                'data' => array('$a' => 'b', 'c' => 'd'),
                'args' => array('111 {{$a}} {{c}} 111'),
                'expectedResult' => '111 {{$a}} d 111',
            ),
            'started - almost finished' => array(
                'data' => array('a' => 'b'),
                'args' => array('111 {{a}'),
                'expectedResult' => '111 {{a}',
            ),
        );
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @param array $args
     * @param string $format
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     * @dataProvider toStringSubExceptionDataProvider
     */
    public function testToStringSubException(array $args, $format)
    {
        $object = $this->getMock('Varien_Object', array('getData'), $args);
        $object->expects($this->once())
            ->method('getData')
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->toString($format);
    }

    public static function toStringSubExceptionDataProvider()
    {
        return array(
            'exception with empty format if-branch' => array(
                array(),
                '',
            ),
            'exception with fine placeholder' => array(
                array(array('a' => 'b')),
                '{{a}}',
            ),
        );
    }
}
