<?php
/**
 * The class is just a descendant of the Varien_Object, nothing is changed
 */
class Zerkella_PhpMage_Varien_Object_Descendant_DataByRef extends Varien_Object
{
    public function setDataByRef(&$data)
    {
        $this->_data = &$data;
    }
}
