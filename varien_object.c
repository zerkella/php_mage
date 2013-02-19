#include "php.h"
#include "zend_interfaces.h"
#include "varien_object.h"
#include "temp.h"

#define INTERNAL_ARR_DEF -97623086 // Default value for array properties, so we know when it was redeclared in subclass

// ---Protected property declarations-------
// Dynamically calculated internal name of property and its hash
typedef struct {
	char *name;
	int name_len;
	ulong hash;
} vo_property_info_t;

// Declaration of properties
typedef struct {
	char *name;
	uint name_len;
	zend_uchar type;
	long default_value;
	vo_property_info_t *internal_info;
} vo_property_declaration_entry_t;

// Macros for property declarations
#define VO_DECLARE_PROP_ARRAY(property_name, num_buckets_initially) {#property_name, sizeof(#property_name) - 1, IS_ARRAY, num_buckets_initially, NULL}
#define VO_DECLARE_PROP_BOOL(property_name, value) {#property_name, sizeof(#property_name) - 1, IS_BOOL, value, NULL}
#define VO_DECLARE_PROP_NULL(property_name) {#property_name, sizeof(#property_name) - 1, IS_NULL, 0, NULL}
#define VO_DECLARE_PROP_END {NULL, 0, 0, 0, NULL}

// Property declaration
static vo_property_declaration_entry_t vo_property_declarations[] = {
	VO_DECLARE_PROP_ARRAY(_data, 16),
	VO_DECLARE_PROP_BOOL(_hasDataChanges, FALSE),
	VO_DECLARE_PROP_NULL(_origData),
	VO_DECLARE_PROP_NULL(_idFieldName),
	VO_DECLARE_PROP_ARRAY(_underscoreCache, 0),
	VO_DECLARE_PROP_BOOL(_isDeleted, FALSE),
	VO_DECLARE_PROP_ARRAY(_oldFieldsMap, 0),
	VO_DECLARE_PROP_ARRAY(_syncFieldsMap, 0),
	VO_DECLARE_PROP_END
};

//---Methods declaration---
PHP_METHOD(Varien_Object, __construct);
PHP_METHOD(Varien_Object, _initOldFieldsMap);
PHP_METHOD(Varien_Object, _prepareSyncFieldsMap);
PHP_METHOD(Varien_Object, _construct);
PHP_METHOD(Varien_Object, getData);

ZEND_BEGIN_ARG_INFO_EX(vo_getData_arg_info, 0, 1, 0)
	ZEND_ARG_INFO(0, key)
	ZEND_ARG_INFO(0, index)
	ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_prepareSyncFieldsMap_arg_info, 0, 1, 0)
	ZEND_END_ARG_INFO()

static const zend_function_entry vo_methods[] = {
	PHP_ME(Varien_Object, __construct, NULL, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
	PHP_ME(Varien_Object, _initOldFieldsMap, NULL, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, _prepareSyncFieldsMap, vo_prepareSyncFieldsMap_arg_info, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, _construct, NULL, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, getData, vo_getData_arg_info, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
	PHP_FE_END
};

//---Used variables---
static zend_class_entry *vo_class;
static int vo_def_props_num;
static vo_property_info_t *vo_data_property_info;

// Forward declarations
static zend_object_value vo_create_handler(zend_class_entry *class_type TSRMLS_DC);
static void vo_create_default_array_properties(zend_object_value *obj_value TSRMLS_DC);
static vo_property_info_t *get_protected_property_info(const char *name, int name_len, int persistent);
static zend_bool def_property_redeclared(const zval *obj_zval, const zend_class_entry *class_type, const vo_property_declaration_entry_t *property_declaration);
static int vo_callback_make_syncFieldsMap(zval **zv TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key);

// Module initialization. Register Varien_Object class.
int mage_varien_object_minit(TSRMLS_D)
{
	zend_class_entry ce;
	int i;
	vo_property_declaration_entry_t *prop_declaration;

	//---Class---
	INIT_CLASS_ENTRY(ce, "Varien_Object", vo_methods);
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
			case IS_ARRAY: 
				/* There is no ability to declare default array property for internal class. And there is no ability to know, that
				 * a subclass has re-declared a property. Thus array properties are initially declared as special int values, 
				 * but are reinitialized to empty arrays in the create_handler.
				 */
				zend_declare_property_long(vo_class, prop_declaration->name, prop_declaration->name_len, INTERNAL_ARR_DEF, ZEND_ACC_PROTECTED TSRMLS_CC);
				break;
			case IS_NULL:
				zend_declare_property_null(vo_class, prop_declaration->name, prop_declaration->name_len, ZEND_ACC_PROTECTED TSRMLS_CC);
				break;
			default:
				php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unknown property declaration type %s:%d", prop_declaration->name, prop_declaration->type);
				break;
		}
		prop_declaration->internal_info = get_protected_property_info(prop_declaration->name, prop_declaration->name_len, 1); 
	};

	// Optimization - cache different values
	vo_def_props_num = zend_hash_num_elements(&vo_class->default_properties);
	vo_data_property_info = get_protected_property_info("_data", sizeof("_data") - 1, TRUE);

	return SUCCESS;
}

// Returns hash of a protected property, searching it in the array of properties
static vo_property_info_t *get_protected_property_info(const char *name, int name_len, int persistent) 
{
	vo_property_info_t *result;
	result = pemalloc(sizeof(vo_property_info_t), persistent);
	zend_mangle_property_name(&result->name, &result->name_len, "*", 1, name, name_len, TRUE); // * = protected property
	result->name_len++; // Somehow additional \0 at the end also should be counted
	result->hash = zend_get_hash_value(result->name, result->name_len);
	return result;
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
	zend_hash_init(object->properties, vo_def_props_num, NULL, ZVAL_PTR_DTOR, FALSE);
	zend_hash_copy(object->properties, &class_type->default_properties, zval_copy_property_ctor(class_type), (void *) &tmp, sizeof(zval *));

	// Update properties that must be arrays by default
	vo_create_default_array_properties(&retval TSRMLS_CC);

	return retval;
}
static inline void vo_create_default_array_properties(zend_object_value *obj_value TSRMLS_DC)
{
	zval *obj_zval;

	int i;
	const vo_property_declaration_entry_t *prop_declaration;
	zval *array_property;
	HashTable *ht;
	zend_class_entry *obj_ce;

	MAKE_STD_ZVAL(obj_zval);
	Z_TYPE_P(obj_zval) = IS_OBJECT;
	Z_OBJVAL_P(obj_zval) = *obj_value;

	obj_ce = Z_OBJCE_P(obj_zval);

	for (i = 0; ; i++) {
		prop_declaration = &vo_property_declarations[i];
		if (!prop_declaration->name_len) {
			break;
		}
		if (prop_declaration->type != IS_ARRAY) {
			continue;
		}

		if (def_property_redeclared(obj_zval, obj_ce, prop_declaration)) {
			continue;
		}

		ALLOC_HASHTABLE(ht);
		if (zend_hash_init(ht, prop_declaration->default_value, NULL, ZVAL_PTR_DTOR, TRUE) == FAILURE) { // Optimization - pre-allocate buffer for "default_value" buckets
			FREE_HASHTABLE(ht);
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for object default property");
		}

		MAKE_STD_ZVAL(array_property);
		Z_TYPE_P(array_property) = IS_ARRAY;
		Z_ARRVAL_P(array_property) = ht;

		zend_update_property(vo_class, obj_zval, prop_declaration->name, prop_declaration->name_len, array_property TSRMLS_CC);

		zval_ptr_dtor(&array_property); // It has been saved saved as object's property, so just decrease refcount
	}

	FREE_ZVAL(obj_zval);
}

static zend_bool def_property_redeclared(const zval *obj_zval, const zend_class_entry *class_type, const vo_property_declaration_entry_t *property_declaration) {
	zval **def_property;

	if (class_type == vo_class) {
		return FALSE; // This is our own class
	}
	
	if (zend_hash_quick_find(&class_type->default_properties, property_declaration->internal_info->name, property_declaration->internal_info->name_len, property_declaration->internal_info->hash, (void **) &def_property) == FAILURE) {
		return TRUE; // Internal name was changed, which means that protected access changed to public, which means property was redeclared
	}

	if ((Z_TYPE_PP(def_property) == IS_LONG) && (Z_LVAL_PP(def_property) == INTERNAL_ARR_DEF)) {
		return FALSE;
	} 

	return TRUE;
}

//public function __construct()
PHP_METHOD(Varien_Object, __construct)
{
	zval *obj_zval = getThis();
	zval *param = NULL;
	int num_args = ZEND_NUM_ARGS();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval *oldFieldsMap, *tmp_zval;
	zend_bool isOldFieldsMap;

	/*
	---PHP---
	$this->_initOldFieldsMap();
	*/
	zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "_initoldfieldsmap", NULL);

	/*
	---PHP---
	if ($this->_oldFieldsMap) {
		$this->_prepareSyncFieldsMap();
	}
	*/
	oldFieldsMap = zend_read_property(obj_ce, obj_zval, "_oldFieldsMap", sizeof("_oldFieldsMap") - 1, FALSE TSRMLS_CC);
	ALLOC_INIT_ZVAL(tmp_zval);
	MAKE_COPY_ZVAL(&oldFieldsMap, tmp_zval);
	convert_to_boolean(tmp_zval);
	isOldFieldsMap = Z_BVAL_P(tmp_zval);
	zval_ptr_dtor(&tmp_zval);

	if (isOldFieldsMap) {
		zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "_preparesyncfieldsmap", NULL);
	}

	/*
	---PHP---
	$args = func_get_args();
	if (empty($args[0])) {
		$args[0] = array();
	}
	$this->_data = $args[0];
	*/
	if (num_args) {
		if ((zend_parse_parameters(num_args TSRMLS_CC, "a!", &param) == SUCCESS)
			&& zend_hash_num_elements(Z_ARRVAL_P(param))) 
		{
			zend_update_property(obj_ce, obj_zval, "_data", sizeof("_data") - 1, param TSRMLS_CC);
		}
	}

	/*
	---PHP---
	$this->_construct();
	*/
	zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "_construct", NULL);
}

//protected function _initOldFieldsMap()
PHP_METHOD(Varien_Object, _initOldFieldsMap)
{
}

//protected function _prepareSyncFieldsMap()
PHP_METHOD(Varien_Object, _prepareSyncFieldsMap)
{
	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval *syncFieldsMap, *oldFieldsMap;
	int num_sync_elements;
	HashTable *ht_for_property;

	/*
	---PHP---
	$old2New = $this->_oldFieldsMap;
	$new2Old = array_flip($this->_oldFieldsMap);
	$this->_syncFieldsMap = array_merge($old2New, $new2Old);
	*/
	oldFieldsMap = zend_read_property(obj_ce, obj_zval, "_oldFieldsMap", sizeof("_oldFieldsMap") - 1, FALSE TSRMLS_CC);
	if (Z_TYPE_P(oldFieldsMap) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_oldFieldsMap is not an array, while processing it through _prepareSyncFieldsMap() method");
	}

	// Create new zval for syncFieldsMap 
	num_sync_elements = zend_hash_num_elements(Z_ARRVAL_P(oldFieldsMap));
	ALLOC_HASHTABLE(ht_for_property);
	if (zend_hash_init(ht_for_property, num_sync_elements * 2, NULL, ZVAL_PTR_DTOR, FALSE) == FAILURE) {
		FREE_HASHTABLE(ht_for_property);
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for _syncFieldsMap");
	}

	syncFieldsMap = zend_read_property(obj_ce, obj_zval, "_syncFieldsMap", sizeof("_syncFieldsMap") - 1, FALSE TSRMLS_CC);
	if (!Z_ISREF_P(syncFieldsMap) && (Z_REFCOUNT_P(syncFieldsMap) > 1)) {
		// Create new zval and set it to object
		ALLOC_INIT_ZVAL(syncFieldsMap);
		zend_update_property(obj_ce, obj_zval, "_syncFieldsMap", sizeof("_syncFieldsMap") - 1, syncFieldsMap TSRMLS_CC);
	} else {
		// Keep current zval, just clean its current content
		zval_dtor(syncFieldsMap);
	}
	Z_TYPE_P(syncFieldsMap) = IS_ARRAY;
	Z_ARRVAL_P(syncFieldsMap) = ht_for_property;
	
	// Copy values from oldFieldsMap
	zend_hash_copy(ht_for_property, Z_ARRVAL_P(oldFieldsMap), zval_add_ref, NULL, sizeof(zval *));
	
	// Add flipped pairs from oldFieldsMap
	zend_hash_apply_with_arguments(Z_ARRVAL_P(oldFieldsMap) TSRMLS_CC, vo_callback_make_syncFieldsMap, 1, ht_for_property);

	/*
	--PHP---
	return $this;
	*/
	zval_ptr_dtor(return_value_ptr);
	*return_value_ptr = obj_zval;
	Z_ADDREF_PP(return_value_ptr);
}

// Put the flipped key->val to the table, which is passed as additional argument
static int vo_callback_make_syncFieldsMap(zval **zv TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	zval *new_zval;
	char *value;
	uint value_len;
	HashTable *target = va_arg(args, HashTable*);

	ALLOC_INIT_ZVAL(new_zval);
	MAKE_COPY_ZVAL(zv, new_zval);
	convert_to_string(new_zval);
	value = Z_STRVAL_P(new_zval);
	value_len = Z_STRLEN_P(new_zval);
	if (hash_key->nKeyLength) {
		Z_STRVAL_P(new_zval) = estrndup(hash_key->arKey, hash_key->nKeyLength - 1);
		Z_STRLEN_P(new_zval) = hash_key->nKeyLength - 1;
	} else {
		Z_STRLEN_P(new_zval) = spprintf(&Z_STRVAL_P(new_zval), 0, "%ld", hash_key->h);
	}

	zend_hash_update(target, value, value_len + 1, &new_zval, sizeof(zval *), NULL); // "update" used instead of "add", so we don't need to react, if the key already exists
	efree(value);

	return ZEND_HASH_APPLY_KEEP;
}

//protected function _construct()
PHP_METHOD(Varien_Object, _construct)
{
}

// public function getData($key='', $index=null)
PHP_METHOD(Varien_Object, getData)
{
	zval *object = getThis();
	int num_args = ZEND_NUM_ARGS();
	zend_bool is_return_whole_data = FALSE;

	char *key = NULL, *index = NULL;
	int key_len, index_len;

	int parse_result;
	zval **extracted_property;

	if (num_args) {
		if (num_args == 1) {
			parse_result = zend_parse_parameters(num_args TSRMLS_CC, "s!", &key, &key_len);
		} else {
			parse_result = zend_parse_parameters(num_args TSRMLS_CC, "s!s!", &key, &key_len, &index, &index_len);
		}
		if (parse_result == FAILURE) {
			RETURN_NULL();
		}
		is_return_whole_data = !key || !key_len;
	} else {
		is_return_whole_data = TRUE;
	}

	if (is_return_whole_data) {
		// We don't need key and index anymore
		if (key) {
			efree(key);
		}
		if (index) {
			efree(index);
		}
		// Find property in the object
		if (zend_hash_quick_find(Z_OBJPROP_P(object), vo_data_property_info->name, vo_data_property_info->name_len, vo_data_property_info->hash, (void**)&extracted_property) == FAILURE) {
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Strange error - couldn't get _data property");
		}
		// Return property
		if (Z_ISREF_PP(extracted_property)) {
			MAKE_COPY_ZVAL(extracted_property, return_value);
		} else {
			zval_ptr_dtor(return_value_ptr);
			*return_value_ptr = *extracted_property;
			Z_ADDREF_PP(return_value_ptr);
		}
	} else {
		// TODO: fill this logic in
		// Process data and index
		if (key) {
			efree(key);
		}
		if (index) {
			efree(index);
		}
		RETURN_LONG(33);
	}
}
