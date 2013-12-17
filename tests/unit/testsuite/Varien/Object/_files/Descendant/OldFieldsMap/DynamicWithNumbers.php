<?php
/**
 * The class, which dynamically sets $_oldFieldsMap property and includes numbers.
 * This is used just to test, that everything goes well with numbers in the extension. Originally,
 * Varien_Object was not intended to work with numbers, so they have issues - i.e. do not map properly.
 */
class Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_DynamicWithNumbers extends Varien_Object
{
    protected function _initOldFieldsMap()
    {
        $this->_oldFieldsMap = array('a' => 'b', 'c' => 'd', 111 => 222);
    }
}
