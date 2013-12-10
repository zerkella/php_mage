#include "php_mage.h" 
#include "varien_object.h"

//---Functions declaration-----------
static const zend_function_entry mage_functions[] = {
	PHP_FE_END
};

PHP_MINIT_FUNCTION(mage);
PHP_RINIT_FUNCTION(mage);

//---Module declaration--------------
zend_module_entry mage_module_entry = {
	STANDARD_MODULE_HEADER,
	PHP_ZERK_MAGE_NAME,
	NULL, /* Functions */
	PHP_MINIT(mage),
	NULL, /* MSHUTDOWN */
	PHP_RINIT(mage),
	NULL, /* RSHUTDOWN */
	NULL, /* MINFO */
	PHP_ZERK_MAGE_VERSION,
	STANDARD_MODULE_PROPERTIES
};
ZEND_GET_MODULE(mage)

PHP_MINIT_FUNCTION(mage)
{
	mage_varien_object_minit(TSRMLS_C);
	return SUCCESS;
}

PHP_RINIT_FUNCTION(mage)
{
	mage_varien_object_rinit(TSRMLS_C);
	return SUCCESS;
}
