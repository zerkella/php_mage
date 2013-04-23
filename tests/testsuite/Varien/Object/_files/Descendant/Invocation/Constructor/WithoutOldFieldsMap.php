<?php
/**
 * Outputs messages in the methods that are supposed to be invoked in the constructor.
 * So it is possible to verify, that all the methods are executed, and in correct order.
 *
 * There is no map of old fields, so one method must not be invoked there.
 */
class Zerkella_PhpMage_Varien_Object_Descendant_Invocation_Constructor_WithoutOldFieldsMap extends Varien_Object
{
    protected function _initOldFieldsMap()
    {
        echo "_initOldFieldsMap()\n";
    }

    protected function _prepareSyncFieldsMap()
    {
        echo "_prepareSyncFieldsMap()\n";
        return $this;
    }

    protected function _addFullNames()
    {
        echo '_addFullNames(): ' , implode(',', $this->_data) , "\n";
    }

    protected function _construct()
    {
        echo '_construct(): ' , implode(',', $this->_data) , "\n";
    }
}
