<?php
/**
 * The file redeclares some properties of Varien_Object
 */
class Varien_Object_Descendant_Properties extends Varien_Object {

    protected $_idFieldName = 'some_id';

    protected $_underscoreCache = array(1, 2, 3);

    public $_oldFieldsMap = 456;

    public $_syncFieldsMap = null;

    protected $_newProperty = array(7, 8, 9);
}
