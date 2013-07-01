<?php
class Varien_Object_methods_getSetIdTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param array $data
     * @param string|null $idField
     * @param mixed $expectedId
     * @dataProvider getIdDataProvider
     */
    public function testGetId(array $data, $idField, $expectedId)
    {
        $object = new Varien_Object($data);
        if ($idField) {
            $object->setIdFieldName($idField);
        }
        $actual = $object->getId();

        // Primary verification
        $this->assertSame($expectedId, $actual);

        // Check that nothing has happened with the id field name
        if ($idField) {
            $this->assertSame($idField, $object->getIdFieldName($idField), 'Id field name has changed somehow');
        }
    }

    /**
     * @return array
     */
    public static function getIdDataProvider()
    {
        $idObject = new StdClass;
        return array(
            'default' => array(
                array(),
                null,
                null
            ),
            'int id at default field name' => array(
                array('id' => 12),
                null,
                12
            ),
            'string id at default field name' => array(
                array('id' => 'my_id'),
                null,
                'my_id'
            ),
            'object id at default field name' => array(
                array('id' => $idObject),
                null,
                $idObject
            ),
            'no id at custom field name' => array(
                array(),
                'custom_id',
                null
            ),
            'int id at custom field name' => array(
                array('custom_id' => 12),
                'custom_id',
                12
            ),
            'string id at custom field name' => array(
                array('custom_id' => 'my_id'),
                'custom_id',
                'my_id'
            ),
            'object id at custom field name' => array(
                array('custom_id' => $idObject),
                'custom_id',
                $idObject
            ),
            'false custom field name' => array(
                array('id' => 12),
                false,
                12
            ),
            'zero custom field name' => array(
                array('id' => 12),
                0,
                12
            ),
            'int custom field name' => array(
                array(144 => 12),
                144,
                12
            ),
        );
    }

    /**
     * @param array $data
     * @param string|null $idField
     * @param mixed $id
     * @param mixed $expectedId
     * @dataProvider setIdDataProvider
     */
    public function testSetId(array $data, $idField, $id, $expectedId)
    {
        $object = new Varien_Object($data);
        if ($idField) {
            $object->setIdFieldName($idField);
        }
        $retval = $object->setId($id);

        // Primary verification
        $this->assertSame($expectedId, $object->getId());
        $this->assertSame($retval, $object);

        // Check that nothing has happened with the id field name
        if ($idField) {
            $this->assertSame($idField, $object->getIdFieldName($idField), 'Id field name has changed somehow');
        }
    }

    /**
     * @return array
     */
    public static function setIdDataProvider()
    {
        $idObject = new StdClass;
        return array(
            'default' => array(
                array(),
                null,
                12,
                12,
            ),
            'int id' => array(
                array('id' => 'a'),
                null,
                12,
                12,
            ),
            'string id' => array(
                array('id' => 'a'),
                null,
                'my_id',
                'my_id',
            ),
            'object id' => array(
                array('id' => 'a'),
                null,
                $idObject,
                $idObject,
            ),
            'int id at custom field name' => array(
                array(),
                'custom_id',
                12,
                12,
            ),
            'string id at custom field name' => array(
                array(),
                'custom_id',
                'my_id',
                'my_id',
            ),
            'object id at custom field name' => array(
                array(),
                'custom_id',
                $idObject,
                $idObject,
            ),
        );
    }

    public function testGetSetIdWhenIdFieldNameChanges()
    {
        $object = new Varien_Object(array('id' => 12, 'custom_id' => 'my_id'));
        $this->assertEquals(12, $object->getId());

        $object->setIdFieldName('custom_id');
        $this->assertEquals('my_id', $object->getId());

        $object->setIdFieldName(false);
        $this->assertEquals(12, $object->getId());

        $object->setIdFieldName(0);
        $this->assertEquals(12, $object->getId());
    }

    public function testGetIdCustomFieldName()
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_IdFieldName(array('id' => 12, 'custom_id' => 'my_id'));
        $this->assertEquals('my_id', $object->getId());
    }

    public function testSetIdCustomFieldName()
    {
        $object = new Zerkella_PhpMage_Varien_Object_Descendant_IdFieldName(array('id' => 12, 'custom_id' => 'my_id'));
        $object->setId('new_id');

        $expected = array('id' => 12, 'custom_id' => 'new_id');
        $actual = $object->getData();
        $this->assertSame($expected, $actual);
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @param string $methodWithException
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     * @dataProvider getIdSubExceptionDataProvider
     *
     */
    public function testGetIdSubException($methodWithException)
    {
        $object = $this->getMock('Varien_Object', array($methodWithException));
        $object->expects($this->once())
            ->method($methodWithException)
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->getId();
    }

    /**
     * @return array
     */
    public static function getIdSubExceptionDataProvider()
    {
        return array(
            array('getIdFieldName'),
            array('_getData'),
        );
    }

    /**
     * Test, that when the method calls other method, and there is an exception, then everything goes fine.
     * A wrong result may be a segmentation fault (i.e. extension didn't check the returned value).
     *
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage some exception
     */
    public function testSetIdSubException()
    {
        $object = $this->getMock('Varien_Object', array('getIdFieldName'));
        $object->expects($this->once())
            ->method('getIdFieldName')
            ->will($this->throwException(new BadMethodCallException('some exception')));
        $result = $object->setId('value');
    }
}
