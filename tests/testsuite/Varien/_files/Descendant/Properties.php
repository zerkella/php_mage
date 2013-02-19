<?php
/**
 * The file redeclares some properties of Varien_Object
 */
class Varien_Object_Descendant_Properties extends Varien_Object
{

    protected $_idFieldName = 'some_id';

    protected $_underscoreCache = 123;

    public $_oldFieldsMap = array(4, 5, 6);

    public $_isDeleted = null;

    protected $_newProperty = array(7, 8, 9);
}
