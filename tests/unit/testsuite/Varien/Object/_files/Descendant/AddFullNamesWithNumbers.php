<?php
/**
 * The file redeclares _oldFieldsMap property of Varien_Object to make _addFullNames() work
 * This is used just to test, that everything goes well with numbers in the extension. Originally,
 * Varien_Object was not intended to work with numbers, so they have issues - i.e. do not map properly.
 */
class Zerkella_PhpMage_Varien_Object_Descendant_AddFullNamesWithNumbers extends Varien_Object
{
    protected $_oldFieldsMap = array(
        'old_property1' => 'new_property1',
        'old_property2' => 'new_property2',
        111 => 333
    );
}
