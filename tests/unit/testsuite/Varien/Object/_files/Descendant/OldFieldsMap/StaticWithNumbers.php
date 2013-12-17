<?php
/**
 * The class, which statically sets $_oldFieldsMap property and includes numbers.
 * This is used just to test, that everything goes well with numbers in the extension. Originally,
 * Varien_Object was not intended to work with numbers, so they have issues - i.e. do not map properly.
 */
class Zerkella_PhpMage_Varien_Object_Descendant_OldFieldsMap_StaticWithNumbers extends Varien_Object
{
    protected $_oldFieldsMap = array('e' => 'f', 'g' => 'h', 333 => 444);
}
