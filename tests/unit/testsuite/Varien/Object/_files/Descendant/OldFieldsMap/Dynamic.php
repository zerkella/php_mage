<?php
/**
 * The class, which dynamically sets $_oldFieldsMap property
 */
class Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_Dynamic extends Varien_Object
{
    protected function _initOldFieldsMap()
    {
        $this->_oldFieldsMap = array('a' => 'b', 'c' => 'd');
    }
}
