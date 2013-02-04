#include "php.h"
#include "varien_object.h"

zend_class_entry *mage_varien_object_ptr;

// Register Varien_Object class
int mage_varien_object_minit(TSRMLS_D)
{
	zend_class_entry ce;
	INIT_CLASS_ENTRY(ce, "Varien_Object", NULL);
	mage_varien_object_ptr = zend_register_internal_class(&ce TSRMLS_CC);
	return SUCCESS;
}
