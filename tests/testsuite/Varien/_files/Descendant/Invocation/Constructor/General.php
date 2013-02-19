<?php
/**
 * Outputs messages in the methods that are supposed to be invoked in the constructor.
 * So it is possible to verify, that all the methods are executed, and in correct order.
 */
class Varien_Object_Descendant_Invocation_Constructor_General extends Varien_Object
{
    protected function _initOldFieldsMap()
    {
        $this->_oldFieldsMap = array('a' => 'b', 'c' => 'd');
        echo "_initOldFieldsMap()\n";
    }

    protected function _prepareSyncFieldsMap()
    {
        echo "_prepareSyncFieldsMap()\n";
        return $this;
    }

    protected function _construct()
    {
        echo '_construct(): ' , implode(',', $this->_data) , "\n";
    }
}
