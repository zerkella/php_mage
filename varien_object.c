#include "php.h"
#include "varien_object.h"

static zend_class_entry *vo_class;
static int vo_def_props_num;

// Declarations
static zend_object_value vo_create_handler(zend_class_entry *type TSRMLS_DC);

// Module initialization. Register Varien_Object class.
int mage_varien_object_minit(TSRMLS_D)
{
	zend_class_entry ce;

	//---Class---
	INIT_CLASS_ENTRY(ce, "Varien_Object", NULL);
	vo_class = zend_register_internal_class(&ce TSRMLS_CC);

	/*
	Create custom "create object" handler, because internal class declarations cannot have arrays, objects or 
	resources as default properties. So we will assign arrays to properties in the custom handler.
	*/
	vo_class->create_object = vo_create_handler;

	//---Properties---
	zend_declare_property_null(vo_class, "_data", sizeof("_data") - 1, ZEND_ACC_PROTECTED TSRMLS_CC);

	vo_def_props_num = zend_hash_num_elements(&vo_class->default_properties);

	return SUCCESS;
}

// Custom handler, called on Varien_Object creation. Initializes default properties that must have array type.
zend_object_value vo_create_handler(zend_class_entry *class_type TSRMLS_DC)
{
	zend_object *object;
	zend_object_value retval;
	zval *obj_zval;
	zval *tmp;

	zval *array_property;
	HashTable *ht;

	// Standard initialization
	retval = zend_objects_new(&object, class_type TSRMLS_CC);
	zend_object_std_init(object, class_type TSRMLS_CC);
	
	// Copy class properties
	ALLOC_HASHTABLE(object->properties);
	zend_hash_init(object->properties, vo_def_props_num, NULL, ZVAL_PTR_DTOR, 0);
	zend_hash_copy(object->properties, &class_type->default_properties, zval_copy_property_ctor(class_type), (void *) &tmp, sizeof(zval *));

	// Update properties that must be arrays by default
	MAKE_STD_ZVAL(obj_zval);
	Z_TYPE_P(obj_zval) = IS_OBJECT;
	Z_OBJVAL_P(obj_zval) = retval;

	ALLOC_HASHTABLE(ht);
	if (zend_hash_init(ht, 16, NULL, ZVAL_PTR_DTOR, 1) == FAILURE) { // Optimization - pre-allocate buffer for 16 buckets
		FREE_HASHTABLE(ht);
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for object default property");
	}

	MAKE_STD_ZVAL(array_property);
	Z_TYPE_P(array_property) = IS_ARRAY;
	Z_ARRVAL_P(array_property) = ht;

	zend_update_property(class_type, obj_zval, "_data", sizeof("_data") - 1, array_property TSRMLS_CC);

	FREE_ZVAL(obj_zval);
	zval_ptr_dtor(&array_property); // It has been saved saved as object property, so just decrease refcount

	return retval;
}
