<?php
/**
 * The file redeclares _oldFieldsMap property of Varien_Object to make _addFullNames() work
 */
class Varien_Object_Descendant_AddFullNames extends Varien_Object
{
    public $_oldFieldsMap = array(
        'old_property1' => 'new_property1',
        'old_property2' => 'new_property2',
        111 => 333
    );
}
