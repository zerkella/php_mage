<?php
class Varien_Object_methods_isDeleted extends PHPUnit_Framework_TestCase
{
    public function testIsDeleted()
    {
        $object = new Varien_Object();
        $this->assertFalse($object->isDeleted());

        // Nothing changes after calling without the param
        $this->assertFalse($object->isDeleted());
    }

    /**
     * @param mixed $param
     * @param mixed $paramLater
     * @dataProvider isDeletedWithParamsDataProvider
     */
    public function testIsDeletedWithParams($param, $paramLater)
    {
        $object = new Varien_Object();
        $this->assertFalse($object->isDeleted());

        // Set first value, ensure that default value (false) is returned
        $result = $object->isDeleted($param);
        $this->assertFalse($result);

        // Set second value, ensure that first value is returned
        $result2 = $object->isDeleted($paramLater);
        $this->assertSame($param, $result2, 'isDeleted() must return current value');

        // Ensure, that new value is returned
        $this->assertSame($paramLater, $object->isDeleted(), 'Ensure, that the last set value is returned');
    }

    /**
     * @return array
     */
    public static function isDeletedWithParamsDataProvider()
    {
        return array(
            'different consequent isDeleted() calls' => array(true, false),
            'same consequent isDeleted() calls' => array(true, true)
        );
    }
}
