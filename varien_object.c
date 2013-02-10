#include "php.h"
#include "varien_object.h"

// ---Protected property declarations-------
typedef struct {
	char *name;
	uint name_len;
	zend_uchar type;
	long default_value;
} vo_property_declaration_entry;

// Macros for property declarations
#define VO_DECLARE_PROP_ARRAY(property_name) {#property_name, sizeof(#property_name) - 1, IS_ARRAY, 0}
#define VO_DECLARE_PROP_BOOL(property_name, value) {#property_name, sizeof(#property_name) - 1, IS_BOOL, value}
#define VO_DECLARE_PROP_NULL(property_name) {#property_name, sizeof(#property_name) - 1, IS_NULL, 0}
#define VO_DECLARE_PROP_END {NULL, 0, 0, 0}

// Property declaration
static const vo_property_declaration_entry vo_property_declarations[] = {
	VO_DECLARE_PROP_ARRAY(_data),
	VO_DECLARE_PROP_BOOL(_hasDataChanges, FALSE),
	VO_DECLARE_PROP_NULL(_origData),
	VO_DECLARE_PROP_NULL(_idFieldName),
	VO_DECLARE_PROP_ARRAY(_underscoreCache),
	VO_DECLARE_PROP_BOOL(_isDeleted, FALSE),
	VO_DECLARE_PROP_ARRAY(_oldFieldsMap),
	VO_DECLARE_PROP_ARRAY(_syncFieldsMap),
	VO_DECLARE_PROP_END
};

// ---Used variables---
static zend_class_entry *vo_class;
static int vo_def_props_num;

// Forward declarations
static zend_object_value vo_create_handler(zend_class_entry *class_type TSRMLS_DC);
static void vo_create_default_array_properties(zend_object_value *obj_value TSRMLS_DC);

// Module initialization. Register Varien_Object class.
int mage_varien_object_minit(TSRMLS_D)
{
	zend_class_entry ce;
	int i;
	const vo_property_declaration_entry *prop_declaration;

	//---Class---
	INIT_CLASS_ENTRY(ce, "Varien_Object", NULL);
	vo_class = zend_register_internal_class(&ce TSRMLS_CC);

	/*
	Create custom "create object" handler, because internal class declarations cannot have arrays, objects or 
	resources as default properties. So we will assign arrays to properties in the custom handler.
	*/
	vo_class->create_object = vo_create_handler;

	//---Properties---
	// Note: array properties are initialized to arrays in create_handler
	for (i = 0; ; i++) {
		prop_declaration = &vo_property_declarations[i];
		if (!prop_declaration->name_len) {
			break;
		}
		switch (prop_declaration->type) {
			case IS_BOOL:
				zend_declare_property_bool(vo_class, prop_declaration->name, prop_declaration->name_len, prop_declaration->default_value, ZEND_ACC_PROTECTED TSRMLS_CC);
				break;
			case IS_NULL:
			case IS_ARRAY: // Arrays are initially declared as NULLs, but are reinitialized to arrays in the create_handler
				zend_declare_property_null(vo_class, prop_declaration->name, prop_declaration->name_len, ZEND_ACC_PROTECTED TSRMLS_CC);
				break;
			default:
				php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unknown property declaration type %s:%d", prop_declaration->name, prop_declaration->type);
				break;
		}
	};

	vo_def_props_num = zend_hash_num_elements(&vo_class->default_properties);

	return SUCCESS;
}

// Custom handler, called on Varien_Object creation. Initializes default properties that must have array type.
static zend_object_value vo_create_handler(zend_class_entry *class_type TSRMLS_DC)
{
	zend_object *object;
	zend_object_value retval;
	zval *tmp;

	// Standard initialization
	retval = zend_objects_new(&object, class_type TSRMLS_CC);
	zend_object_std_init(object, class_type TSRMLS_CC);
	
	// Copy class properties
	ALLOC_HASHTABLE(object->properties);
	zend_hash_init(object->properties, vo_def_props_num, NULL, ZVAL_PTR_DTOR, 0);
	zend_hash_copy(object->properties, &class_type->default_properties, zval_copy_property_ctor(class_type), (void *) &tmp, sizeof(zval *));

	// Update properties that must be arrays by default
	vo_create_default_array_properties(&retval TSRMLS_CC);

	return retval;
}

static void vo_create_default_array_properties(zend_object_value *obj_value TSRMLS_DC)
{
	zval *obj_zval;

	int i;
	const vo_property_declaration_entry *prop_declaration;
	zval *array_property;
	HashTable *ht;

	MAKE_STD_ZVAL(obj_zval);
	Z_TYPE_P(obj_zval) = IS_OBJECT;
	Z_OBJVAL_P(obj_zval) = *obj_value;

	for (i = 0; ; i++) {
		prop_declaration = &vo_property_declarations[i];
		if (!prop_declaration->name_len) {
			break;
		}
		if (prop_declaration->type != IS_ARRAY) {
			continue;
		}

		ALLOC_HASHTABLE(ht);
		if (zend_hash_init(ht, prop_declaration->default_value, NULL, ZVAL_PTR_DTOR, 1) == FAILURE) { // Optimization - pre-allocate buffer for "default_value" buckets
			FREE_HASHTABLE(ht);
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for object default property");
		}

		MAKE_STD_ZVAL(array_property);
		Z_TYPE_P(array_property) = IS_ARRAY;
		Z_ARRVAL_P(array_property) = ht;

		zend_update_property(vo_class, obj_zval, prop_declaration->name, prop_declaration->name_len, array_property TSRMLS_CC);

		zval_ptr_dtor(&array_property); // It has been saved saved as object property, so just decrease refcount
	}

	FREE_ZVAL(obj_zval);
}
