<?php
/**
 * The file declares $_dirty property, which is missed from original Varien_Object class
 */
class Zerkella_PhpMage_Varien_Object_Descendant_BugFix_DirtyProperty extends Varien_Object
{
    protected $_dirty = array();
}
