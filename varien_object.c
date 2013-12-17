#include "php.h"
#include "zend_interfaces.h"
#include "zend_exceptions.h"
#include "ext/standard/php_smart_str.h"
#include "ext/standard/php_string.h"
#include "varien_object.h"
#include <stdio.h>

#define INTERNAL_ARR_DEF -97623086 /* Default value for array properties, so we know when it was redeclared in subclass */

/* ---Protected property declarations-------*/
/* Dynamically calculated internal name of property and its hash */
typedef struct {
	char *name;
	int name_len;
	ulong hash;
} vo_property_info_t;

/* Declaration of properties */
typedef struct {
	char *name;
	uint name_len;
	zend_uchar type;
	int flags;
	long default_value;
	vo_property_info_t *internal_info;
} vo_property_declaration_entry_t;

/* Macros for property declarations */
#define VO_DECLARE_PROP_ARRAY(property_name, flags, num_buckets_initially) {#property_name, sizeof(#property_name) - 1, IS_ARRAY, flags, num_buckets_initially, NULL}
#define VO_DECLARE_PROP_BOOL(property_name, flags, value) {#property_name, sizeof(#property_name) - 1, IS_BOOL, flags, value, NULL}
#define VO_DECLARE_PROP_NULL(property_name, flags) {#property_name, sizeof(#property_name) - 1, IS_NULL, flags, 0, NULL}
#define VO_DECLARE_PROP_END {NULL, 0, 0, 0, 0, NULL}

/* Property declaration */
static vo_property_declaration_entry_t vo_property_declarations[] = {
	VO_DECLARE_PROP_ARRAY(_data, ZEND_ACC_PROTECTED, 16),
	VO_DECLARE_PROP_BOOL(_hasDataChanges, ZEND_ACC_PROTECTED, FALSE),
	VO_DECLARE_PROP_NULL(_origData, ZEND_ACC_PROTECTED),
	VO_DECLARE_PROP_NULL(_idFieldName, ZEND_ACC_PROTECTED),
	VO_DECLARE_PROP_ARRAY(_underscoreCache, ZEND_ACC_PROTECTED | ZEND_ACC_STATIC, 0),
	VO_DECLARE_PROP_BOOL(_isDeleted, ZEND_ACC_PROTECTED, FALSE),
	VO_DECLARE_PROP_ARRAY(_oldFieldsMap, ZEND_ACC_PROTECTED, 0),
	VO_DECLARE_PROP_ARRAY(_syncFieldsMap, ZEND_ACC_PROTECTED, 0),
	VO_DECLARE_PROP_NULL(_dirty, ZEND_ACC_PROTECTED),
	VO_DECLARE_PROP_END
};

/*---Methods declaration---*/
PHP_METHOD(Varien_Object, __construct);
PHP_METHOD(Varien_Object, _initOldFieldsMap);
PHP_METHOD(Varien_Object, _prepareSyncFieldsMap);
PHP_METHOD(Varien_Object, _addFullNames);
PHP_METHOD(Varien_Object, _construct);
PHP_METHOD(Varien_Object, getData);
PHP_METHOD(Varien_Object, setData);
PHP_METHOD(Varien_Object, hasDataChanges);
PHP_METHOD(Varien_Object, isDeleted);
PHP_METHOD(Varien_Object, setIdFieldName);
PHP_METHOD(Varien_Object, getIdFieldName);
PHP_METHOD(Varien_Object, getId);
PHP_METHOD(Varien_Object, setId);
PHP_METHOD(Varien_Object, addData);
PHP_METHOD(Varien_Object, unsetData);
PHP_METHOD(Varien_Object, unsetOldData);
PHP_METHOD(Varien_Object, _getData);
PHP_METHOD(Varien_Object, setDataUsingMethod);
PHP_METHOD(Varien_Object, getDataUsingMethod);
PHP_METHOD(Varien_Object, getDataSetDefault);
PHP_METHOD(Varien_Object, hasData);
PHP_METHOD(Varien_Object, __toArray);
PHP_METHOD(Varien_Object, toArray);
PHP_METHOD(Varien_Object, _prepareArray);
PHP_METHOD(Varien_Object, __toXml);
PHP_METHOD(Varien_Object, toXml);
PHP_METHOD(Varien_Object, __toJson);
PHP_METHOD(Varien_Object, toJson);
PHP_METHOD(Varien_Object, toString);
PHP_METHOD(Varien_Object, __call);
PHP_METHOD(Varien_Object, __get);
PHP_METHOD(Varien_Object, __set);
PHP_METHOD(Varien_Object, isEmpty);
PHP_METHOD(Varien_Object, _underscore);
PHP_METHOD(Varien_Object, _camelize);
PHP_METHOD(Varien_Object, serialize);
PHP_METHOD(Varien_Object, getOrigData);
PHP_METHOD(Varien_Object, setOrigData);
PHP_METHOD(Varien_Object, dataHasChangedFor);
PHP_METHOD(Varien_Object, setDataChanges);
PHP_METHOD(Varien_Object, debug);
PHP_METHOD(Varien_Object, offsetSet);
PHP_METHOD(Varien_Object, offsetExists);
PHP_METHOD(Varien_Object, offsetUnset);
PHP_METHOD(Varien_Object, offsetGet);
PHP_METHOD(Varien_Object, isDirty);
PHP_METHOD(Varien_Object, flagDirty);

ZEND_BEGIN_ARG_INFO_EX(vo_getData_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, key)
	ZEND_ARG_INFO(0, index)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_setData_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, key)
	ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_isDeleted_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, isDeleted)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_setIdFieldName_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_setId_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_addData_arg_info, 0, 0, 1)
	ZEND_ARG_ARRAY_INFO(0, arr, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_unsetData_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, key)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_unsetOldData_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, key)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo__getData_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, key)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_setDataUsingMethod_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, key)
	ZEND_ARG_INFO(0, args)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_getDataUsingMethod_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, key)
	ZEND_ARG_INFO(0, args)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_getDataSetDefault_arg_info, 0, 0, 2)
	ZEND_ARG_INFO(0, key)
	ZEND_ARG_INFO(0, default)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_hasData_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, key)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_toArray_arg_info, 0, 0, 0)
	ZEND_ARG_ARRAY_INFO(0, arrAttributes, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo__prepareArray_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(1, arr)
	ZEND_ARG_ARRAY_INFO(0, elements, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_toXml_arg_info, 0, 0, 0)
	ZEND_ARG_ARRAY_INFO(0, arrAttributes, 0)
	ZEND_ARG_INFO(0, rootName)
	ZEND_ARG_INFO(0, addOpenTag)
	ZEND_ARG_INFO(0, addCdata)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_toJson_arg_info, 0, 0, 0)
	ZEND_ARG_ARRAY_INFO(0, arrAttributes, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_toString_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, format)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo___call_arg_info, 0, 0, 2)
	ZEND_ARG_INFO(0, method)
	ZEND_ARG_INFO(0, args)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo___get_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, var)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo___set_arg_info, 0, 0, 2)
	ZEND_ARG_INFO(0, var)
	ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo__underscore_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo__camelize_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, name)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_serialize_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, attributes)
	ZEND_ARG_INFO(0, valueSeparator)
	ZEND_ARG_INFO(0, fieldSeparator)
	ZEND_ARG_INFO(0, quote)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_getOrigData_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, key)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_setOrigData_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, key)
	ZEND_ARG_INFO(0, data)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_dataHasChangedFor_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, field)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_setDataChanges_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_debug_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, data)
	ZEND_ARG_INFO(0, objects)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_offsetSet_arg_info, 0, 0, 2)
	ZEND_ARG_INFO(0, offset)
	ZEND_ARG_INFO(0, value)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_offsetExists_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, offset)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_offsetUnset_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, offset)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_offsetGet_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, offset)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_isDirty_arg_info, 0, 0, 0)
	ZEND_ARG_INFO(0, field)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(vo_flagDirty_arg_info, 0, 0, 1)
	ZEND_ARG_INFO(0, field)
	ZEND_ARG_INFO(0, flag)
ZEND_END_ARG_INFO()

static const zend_function_entry vo_methods[] = {
	PHP_ME(Varien_Object, __construct, NULL, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
	PHP_ME(Varien_Object, _initOldFieldsMap, NULL, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, _prepareSyncFieldsMap, NULL, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, _addFullNames, NULL, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, _construct, NULL, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, getData, vo_getData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, setData, vo_setData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, hasDataChanges, NULL, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, isDeleted, vo_isDeleted_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, setIdFieldName, vo_setIdFieldName_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, getIdFieldName, NULL, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, getId, NULL, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, setId, vo_setId_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, addData, vo_addData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, unsetData, vo_unsetData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, unsetOldData, vo_unsetOldData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, _getData, NULL, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, setDataUsingMethod, vo_setDataUsingMethod_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, getDataUsingMethod, vo_getDataUsingMethod_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, getDataSetDefault, vo_getDataSetDefault_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, hasData, vo_hasData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, __toArray, vo_toArray_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, toArray, vo_toArray_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, _prepareArray, vo__prepareArray_arg_info, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, __toXml, vo_toXml_arg_info, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, toXml, vo_toXml_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, __toJson, vo_toJson_arg_info, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, toJson, vo_toJson_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, toString, vo_toString_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, __call, vo___call_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, __get, vo___get_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, __set, vo___set_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, isEmpty, NULL, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, _underscore, vo__underscore_arg_info, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, _camelize, vo__camelize_arg_info, ZEND_ACC_PROTECTED)
	PHP_ME(Varien_Object, serialize, vo_serialize_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, getOrigData, vo_getOrigData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, setOrigData, vo_setOrigData_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, dataHasChangedFor, vo_dataHasChangedFor_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, setDataChanges, vo_setDataChanges_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, debug, vo_debug_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, offsetSet, vo_offsetSet_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, offsetExists, vo_offsetExists_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, offsetUnset, vo_offsetUnset_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, offsetGet, vo_offsetGet_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, isDirty, vo_isDirty_arg_info, ZEND_ACC_PUBLIC)
	PHP_ME(Varien_Object, flagDirty, vo_flagDirty_arg_info, ZEND_ACC_PUBLIC)
	PHP_FE_END
};

/*---Used variables---*/
static zend_class_entry *vo_class;
static int vo_def_props_num;
static vo_property_info_t *vo_data_property_info, *vo_isDeleted_property_info, *vo_idFieldName_property_info, 
	*vo_syncFieldsMap_property_info, *vo_oldFieldsMap_property_info, *vo_hasDataChanges_property_info,
	*vo_origData_property_info, *vo_dirty_property_info;

/* Forward declarations */
static zend_object_value vo_create_handler(zend_class_entry *class_type TSRMLS_DC);
static void vo_create_default_array_properties(zend_object_value *obj_value TSRMLS_DC);
static void vo_create_default_array_static_properties(TSRMLS_DC);
static vo_property_info_t *get_protected_property_info(const char *name, int name_len, int persistent);
static zend_bool def_property_redeclared(const zval *obj_zval, const zend_class_entry *class_type, const vo_property_declaration_entry_t *property_declaration);
static int vo_callback_start_syncFieldsMap(zval **zv TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key);
static int vo_callback_make_syncFieldsMap(zval **zv TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key);
static void vo_convert_htmlentities(zval * value, char **result_str, uint *result_len);

/* Pseudo functions */
#define VO_EXTRACT_PROPERTY(property_name, obj_zval_p, property_zval_ppp) \
{ \
	if (zend_hash_quick_find(Z_OBJPROP_P(obj_zval_p), vo##property_name##_property_info->name, vo##property_name##_property_info->name_len, vo##property_name##_property_info->hash, (void**)property_zval_ppp) == FAILURE) { \
	    /* Access to the property was changed, it cannot be found using cached hash. Fall back to standard mechanism of property fetching. */ \
		zval *tmp_extracted_##property_name; \
		tmp_extracted_##property_name = zend_read_property(Z_OBJCE_P(obj_zval_p), obj_zval_p, #property_name, sizeof(#property_name) - 1, FALSE TSRMLS_CC); \
		*property_zval_ppp = &tmp_extracted_##property_name; \
	} \
}

#define VO_VERIFY_SCALAR_INCOMING_VALUE_P(zv, name, action) \
if (zv) { \
	zend_uchar verify_type; \
    verify_type = Z_TYPE_P(zv); \
	switch (verify_type) { \
		case IS_NULL: \
		case IS_LONG: \
		case IS_DOUBLE: \
		case IS_BOOL: \
		case IS_STRING: \
		case IS_CONSTANT: \
			break; \
		default: \
			php_error_docref(NULL TSRMLS_CC, E_WARNING, "Illegal value passed for " name); \
			action; \
			break; \
	} \
}

/* Module initialization. Register Varien_Object class. */
int mage_varien_object_minit(TSRMLS_D)
{
	zend_class_entry ce;
	int i;
	vo_property_declaration_entry_t *prop_declaration;

	/*---Class---*/
	INIT_CLASS_ENTRY(ce, "Varien_Object", vo_methods);
	vo_class = zend_register_internal_class(&ce TSRMLS_CC);
	zend_class_implements(vo_class TSRMLS_CC, 1, zend_ce_arrayaccess);

	/*
	Create custom "create object" handler, because internal class declarations cannot have arrays, objects or 
	resources as default properties. So we will assign arrays to properties in the custom handler.
	*/
	vo_class->create_object = vo_create_handler;

	/*---Properties---*/
	/* Note: array properties are initialized to arrays in create_handler */
	for (i = 0; ; i++) {
		prop_declaration = &vo_property_declarations[i];
		if (!prop_declaration->name_len) {
			break;
		}
		switch (prop_declaration->type) {
			case IS_BOOL:
				zend_declare_property_bool(vo_class, prop_declaration->name, prop_declaration->name_len, prop_declaration->default_value, prop_declaration->flags TSRMLS_CC);
				break;
			case IS_ARRAY: 
				/* There is no ability to declare default array property for internal class. And there is no ability to know, that
				 * a subclass has re-declared a property. Thus array properties are initially declared as special int values, 
				 * but are reinitialized to empty arrays in the create_handler.
				 */
				zend_declare_property_long(vo_class, prop_declaration->name, prop_declaration->name_len, INTERNAL_ARR_DEF, prop_declaration->flags TSRMLS_CC);
				break;
			case IS_NULL:
				zend_declare_property_null(vo_class, prop_declaration->name, prop_declaration->name_len, prop_declaration->flags TSRMLS_CC);
				break;
			default:
				php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unknown property declaration type %s:%d", prop_declaration->name, prop_declaration->type);
				break;
		}
		prop_declaration->internal_info = get_protected_property_info(prop_declaration->name, prop_declaration->name_len, TRUE); 
	};

	/* Optimization - cache different values */
	vo_def_props_num = zend_hash_num_elements(&vo_class->default_properties);
	vo_data_property_info = get_protected_property_info("_data", sizeof("_data") - 1, TRUE);
	vo_isDeleted_property_info = get_protected_property_info("_isDeleted", sizeof("_isDeleted") - 1, TRUE);
	vo_idFieldName_property_info = get_protected_property_info("_idFieldName", sizeof("_idFieldName") - 1, TRUE);
	vo_syncFieldsMap_property_info = get_protected_property_info("_syncFieldsMap", sizeof("_syncFieldsMap") - 1, TRUE);
	vo_oldFieldsMap_property_info = get_protected_property_info("_oldFieldsMap", sizeof("_oldFieldsMap") - 1, TRUE);
	vo_hasDataChanges_property_info = get_protected_property_info("_hasDataChanges", sizeof("_hasDataChanges") - 1, TRUE);
	vo_origData_property_info = get_protected_property_info("_origData", sizeof("_origData") - 1, TRUE);
	vo_dirty_property_info = get_protected_property_info("_dirty", sizeof("_dirty") - 1, TRUE);

	return SUCCESS;
}

/* Returns hash of a protected property, searching it in the array of properties */
static vo_property_info_t *get_protected_property_info(const char *name, int name_len, int persistent) 
{
	vo_property_info_t *result;
	result = pemalloc(sizeof(vo_property_info_t), persistent);
	zend_mangle_property_name(&result->name, &result->name_len, "*", 1, name, name_len, TRUE); /* Star (*) means protected property */
	result->name_len++; /* Somehow additional \0 at the end also should be counted */
	result->hash = zend_get_hash_value(result->name, result->name_len);
	return result;
}

/* Custom handler, called on Varien_Object creation. Initializes default properties that must have array type. */
static zend_object_value vo_create_handler(zend_class_entry *class_type TSRMLS_DC)
{
	zend_object *object;
	zend_object_value retval;
	zval *tmp;

	/* Standard initialization */
	retval = zend_objects_new(&object, class_type TSRMLS_CC);
	zend_object_std_init(object, class_type TSRMLS_CC);
	
	/* Copy class properties */
	ALLOC_HASHTABLE(object->properties);
	zend_hash_init(object->properties, vo_def_props_num, NULL, ZVAL_PTR_DTOR, FALSE);
	zend_hash_copy(object->properties, &class_type->default_properties, zval_copy_property_ctor(class_type), (void *) &tmp, sizeof(zval *));

	/* Update properties that must be arrays by default */
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
		if (prop_declaration->flags & ZEND_ACC_STATIC) {
			continue;
		}

		if (def_property_redeclared(obj_zval, obj_ce, prop_declaration)) {
			continue;
		}

		ALLOC_HASHTABLE(ht);
		if (zend_hash_init(ht, prop_declaration->default_value, NULL, ZVAL_PTR_DTOR, TRUE) == FAILURE) { /* Optimization - pre-allocate buffer for "default_value" buckets */
			FREE_HASHTABLE(ht);
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for object default property");
		}

		MAKE_STD_ZVAL(array_property);
		Z_TYPE_P(array_property) = IS_ARRAY;
		Z_ARRVAL_P(array_property) = ht;
		zend_update_property(vo_class, obj_zval, prop_declaration->name, prop_declaration->name_len, array_property TSRMLS_CC);

		zval_ptr_dtor(&array_property); /* It has been saved saved as object's property, so just decrease refcount */
	}

	FREE_ZVAL(obj_zval);
}

/* Request initialization. Create static members of Varien_Object class. */
int mage_varien_object_rinit(TSRMLS_D)
{
	vo_create_default_array_static_properties(TSRMLS_C);
	return SUCCESS;
}

static inline void vo_create_default_array_static_properties(TSRMLS_D)
{
	int i;
	const vo_property_declaration_entry_t *prop_declaration;
	zval *array_property;
	HashTable *ht;

	for (i = 0; ; i++) {
		prop_declaration = &vo_property_declarations[i];
		if (!prop_declaration->name_len) {
			break;
		}
		if (prop_declaration->type != IS_ARRAY) {
			continue;
		}
		if (!(prop_declaration->flags & ZEND_ACC_STATIC)) {
			continue;
		}

		ALLOC_HASHTABLE(ht);
		if (zend_hash_init(ht, prop_declaration->default_value, NULL, ZVAL_PTR_DTOR, TRUE) == FAILURE) { /* Optimization - pre-allocate buffer for "default_value" buckets */
			FREE_HASHTABLE(ht);
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for object default property");
		}

		MAKE_STD_ZVAL(array_property);
		Z_TYPE_P(array_property) = IS_ARRAY;
		Z_ARRVAL_P(array_property) = ht;
		zend_update_static_property(vo_class, prop_declaration->name, prop_declaration->name_len, array_property TSRMLS_CC);

		zval_ptr_dtor(&array_property); /* It has been saved saved as class's property, so just decrease refcount */
	}
}

static zend_bool def_property_redeclared(const zval *obj_zval, const zend_class_entry *class_type, const vo_property_declaration_entry_t *property_declaration) {
	zval **def_property;

	if (class_type == vo_class) {
		return FALSE; /* This is our own class */
	}
	
	if (zend_hash_quick_find(&class_type->default_properties, property_declaration->internal_info->name, property_declaration->internal_info->name_len, property_declaration->internal_info->hash, (void **) &def_property) == FAILURE) {
		return TRUE; /* Internal name was changed, which means that protected access changed to public, which means property was redeclared */
	}

	if ((Z_TYPE_PP(def_property) == IS_LONG) && (Z_LVAL_PP(def_property) == INTERNAL_ARR_DEF)) {
		return FALSE;
	} 

	return TRUE;
}

/*
Extract variable value either as long or as string (conversion to string is done, if value is not int, bool or string). 
If extracted as long, then val_str will be NULL. 
If val_str must be disposed after usage, then dispose_str will be TRUE.
*/
static inline void expand_to_long_or_string_array_key(zval *z, long *val_long, char **val_str, uint *val_str_len, zend_bool *is_dispose_str TSRMLS_DC)
{
	zval *tmp;
	zend_uchar type = z ? Z_TYPE_P(z) : IS_NULL;

	switch (type) {
		case IS_LONG:
			*val_long = Z_LVAL_P(z);
			*val_str = NULL;
			*is_dispose_str = FALSE;
			break;
		case IS_STRING:
			*val_str = Z_STRVAL_P(z);
			*val_str_len = Z_STRLEN_P(z);
			*is_dispose_str = FALSE;
			break;
		case IS_BOOL:
			*val_long = Z_BVAL_P(z) ? 1 : 0;
			*val_str = NULL;
			*is_dispose_str = FALSE;
			break;
		case IS_NULL:
			*val_str = estrndup("", 1);
			*val_str_len = 0;
			*is_dispose_str = TRUE;
			break;
		default:
			ALLOC_ZVAL(tmp);
			MAKE_COPY_ZVAL(&z, tmp);
			convert_to_string(tmp);
			*val_str = Z_STRVAL_P(tmp);
			*val_str_len = Z_STRLEN_P(tmp);
			FREE_ZVAL(tmp);
			*is_dispose_str = TRUE;
			break;
	}
}

/* public function __construct() */
PHP_METHOD(Varien_Object, __construct)
{
	zval *obj_zval = getThis();
	zval *param = NULL;
	int num_args = ZEND_NUM_ARGS();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval **oldFieldsMap;

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
	VO_EXTRACT_PROPERTY(_oldFieldsMap, obj_zval, &oldFieldsMap);
	if (i_zend_is_true(*oldFieldsMap)) {
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
		if (zend_parse_parameters(num_args TSRMLS_CC, "z!", &param) == SUCCESS) {
			if (param) {
				if (Z_TYPE_P(param) == IS_ARRAY) {
					if (zend_hash_num_elements(Z_ARRVAL_P(param))) {
						zend_update_property(obj_ce, obj_zval, "_data", sizeof("_data") - 1, param TSRMLS_CC);
					}
				} else {
					php_error(E_WARNING, "Incoming parameter to Varien_Object::__construct() must be an array");
				}
			}
		} else {
			return;
		}
	}

		/*
	---PHP---
	$this->_addFullNames();
	*/
	zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "_addfullnames", NULL);

	/*
	---PHP---
	$this->_construct();
	*/
	zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "_construct", NULL);
}

/* protected function _initOldFieldsMap() */
PHP_METHOD(Varien_Object, _initOldFieldsMap)
{
}

/* protected function _prepareSyncFieldsMap() */
PHP_METHOD(Varien_Object, _prepareSyncFieldsMap)
{
	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval **syncFieldsMap_pp, **oldFieldsMap_pp;
	zval *syncFieldsMap, *oldFieldsMap;
	int num_sync_elements;
	HashTable *ht_for_property;
	int next_long_index;

	/*
	---PHP---
	$old2New = $this->_oldFieldsMap;
	$new2Old = array_flip($this->_oldFieldsMap);
	$this->_syncFieldsMap = array_merge($old2New, $new2Old);
	*/
	VO_EXTRACT_PROPERTY(_oldFieldsMap, obj_zval, &oldFieldsMap_pp);
	oldFieldsMap = *oldFieldsMap_pp;
	if (Z_TYPE_P(oldFieldsMap) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_oldFieldsMap is not an array, while processing it through _prepareSyncFieldsMap() method");
	}

	/* Create new zval for syncFieldsMap */
	num_sync_elements = zend_hash_num_elements(Z_ARRVAL_P(oldFieldsMap));
	ALLOC_HASHTABLE(ht_for_property);
	if (zend_hash_init(ht_for_property, num_sync_elements * 2, NULL, ZVAL_PTR_DTOR, FALSE) == FAILURE) {
		FREE_HASHTABLE(ht_for_property);
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for _syncFieldsMap");
	}

	VO_EXTRACT_PROPERTY(_syncFieldsMap, obj_zval, &syncFieldsMap_pp);
	syncFieldsMap = *syncFieldsMap_pp;
	if (!Z_ISREF_P(syncFieldsMap) && (Z_REFCOUNT_P(syncFieldsMap) > 1)) {
		/* Create new zval and set it to object */
		ALLOC_INIT_ZVAL(syncFieldsMap);
		zend_update_property(obj_ce, obj_zval, "_syncFieldsMap", sizeof("_syncFieldsMap") - 1, syncFieldsMap TSRMLS_CC);
	} else {
		/* Keep current zval, just clean its current content */
		zval_dtor(syncFieldsMap);
	}
	Z_TYPE_P(syncFieldsMap) = IS_ARRAY;
	Z_ARRVAL_P(syncFieldsMap) = ht_for_property;
	
	/* Copy values from oldFieldsMap, but change all int indexes to start from 0 */
	next_long_index = 0;
	zend_hash_apply_with_arguments(Z_ARRVAL_P(oldFieldsMap) TSRMLS_CC, vo_callback_start_syncFieldsMap, 1, ht_for_property, &next_long_index);
	
	/* Add flipped pairs from oldFieldsMap, but int indexes must continue incrementing */
	zend_hash_apply_with_arguments(Z_ARRVAL_P(oldFieldsMap) TSRMLS_CC, vo_callback_make_syncFieldsMap, 1, ht_for_property, &next_long_index);

	/*
	--PHP---
	return $this;
	*/
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* Copy zvalue to the hash table, which is passed as additional argument */
static int vo_callback_start_syncFieldsMap(zval **zv TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	HashTable *target = va_arg(args, HashTable*);
	int *next_long_index_p = va_arg(args, int*);

	Z_ADDREF_PP(zv);

	/* Create new zval, which contains the key */
	if (hash_key->nKeyLength) {
		zend_hash_update(target, hash_key->arKey, hash_key->nKeyLength, zv, sizeof(zval *), NULL);
	} else {
		zend_hash_index_update(target, *next_long_index_p, zv, sizeof(zval *), NULL);
		(*next_long_index_p)++;
	}
	return ZEND_HASH_APPLY_KEEP;
}

/* Put the flipped key->val to the table, which is passed as additional argument */
static int vo_callback_make_syncFieldsMap(zval **zv TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	zval *new_zval;
	HashTable *target = va_arg(args, HashTable*);
	int *next_long_index_p = va_arg(args, int*);

	/* Create new zval, which contains the key */
	ALLOC_INIT_ZVAL(new_zval);
	if (hash_key->nKeyLength) {
		ZVAL_STRINGL(new_zval, hash_key->arKey, hash_key->nKeyLength - 1, TRUE);
	} else {
		ZVAL_LONG(new_zval, hash_key->h);
	}

	/* Put it either under hash string, or index, depending on zval extracted.
	   "update" is used instead of "add", so we don't need to react, if the key already exists */
	switch (Z_TYPE_PP(zv)) {
		case IS_STRING:
			zend_hash_update(target, Z_STRVAL_PP(zv), Z_STRLEN_PP(zv) + 1, &new_zval, sizeof(zval *), NULL);
			break;
		case IS_LONG:
			zend_hash_index_update(target, *next_long_index_p, &new_zval, sizeof(zval *), NULL);
			(*next_long_index_p)++;
			break;
		default:
			zval_ptr_dtor(&new_zval);
			php_error_docref(NULL TSRMLS_CC, E_WARNING, "oldFieldsMap must contain only STRING or INTEGER values!");
	}
	return ZEND_HASH_APPLY_KEEP;
}

/* protected function _addFullNames() */
PHP_METHOD(Varien_Object, _addFullNames)
{
	/*
	---PHP---
	$existedShortKeys = array_intersect($this->_syncFieldsMap, array_keys($this->_data));
	if (!empty($existedShortKeys)) {
		foreach ($existedShortKeys as $key) {
			$fullFieldName = array_search($key, $this->_syncFieldsMap);
			$this->_data[$fullFieldName] = $this->_data[$key];
		}
	}

	---Interpretation---
	Go through keys of _data, write each synced value under synced key
	*/
	zval *obj_zval = getThis();
	zval **data;
	zval **syncFieldsMap;
	int num_sync_elements;
	int num_data_elements;
	int total_to_sync, i;
	HashTable *ht_data, *ht_syncFieldsMap;
	int current_key_type;
	ulong current_index;
	char *current_key;
	uint current_key_len;
	zval **found_zval;
	int found_result;
	zval **sync_to;

	typedef struct {
		char *key_name;
		uint key_name_len;
		ulong key_hash;
		zval *value;
	} key_info_t;
	key_info_t *copy_to_keys;
	key_info_t *key_info; 

	/* Extract and check properties */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}

	num_data_elements = zend_hash_num_elements(Z_ARRVAL_PP(data));
	if (!num_data_elements) {
		return;
	}

	VO_EXTRACT_PROPERTY(_syncFieldsMap, obj_zval, &syncFieldsMap);
	if (!syncFieldsMap || !(*syncFieldsMap) || (Z_TYPE_PP(syncFieldsMap) != IS_ARRAY)) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_syncFieldsMap property must be array");
	}

	ht_syncFieldsMap = Z_ARRVAL_PP(syncFieldsMap);
	num_sync_elements = zend_hash_num_elements(ht_syncFieldsMap);
	if (!num_sync_elements) {
		return;
	}

	/* Iterate over syncFieldsMap and find the keys, to which we will sync values from _data. */
	SEPARATE_ZVAL_IF_NOT_REF(data);
	ht_data = Z_ARRVAL_PP(data);
	copy_to_keys = emalloc(num_sync_elements * sizeof(key_info_t)); /* Allocate maximal possible array to hold things we will sync */
	total_to_sync = 0;
	for (zend_hash_internal_pointer_reset(ht_syncFieldsMap); zend_hash_has_more_elements(ht_syncFieldsMap) == SUCCESS; zend_hash_move_forward(ht_syncFieldsMap)) {
		/* Extract current key */
		current_key_type = zend_hash_get_current_key_ex(ht_syncFieldsMap, &current_key, &current_key_len, &current_index, FALSE, NULL);

		/* Try to extract the value at _data[key]. Success means, that we have something to sync. */
		if (current_key_type == HASH_KEY_IS_LONG) {
			found_result = zend_hash_index_find(ht_data, current_index, (void **) &found_zval);
		} else {
			found_result = zend_hash_find(ht_data, current_key, current_key_len, (void **) &found_zval);
		}
		if (found_result == FAILURE) {
			continue;
		}

		/* Extract the "sync to" from _syncFieldsMap - i.e. the key, where the data value must be synced to */
		zend_hash_get_current_data(ht_syncFieldsMap, (void **) &sync_to);

		key_info = &copy_to_keys[total_to_sync];
		switch (Z_TYPE_PP(sync_to)) {
			case IS_LONG:
				key_info->key_name = NULL;
				key_info->key_hash = Z_LVAL_PP(sync_to);
				break;
			case IS_STRING:
				key_info->key_name = Z_STRVAL_PP(sync_to);
				key_info->key_name_len = Z_STRLEN_PP(sync_to);
				break;
			default:
				efree(copy_to_keys);
				php_error_docref(NULL TSRMLS_CC, E_ERROR, "_syncFieldsMap entries may be of INTEGER or STRING type only");
				break;
		}
		key_info->value = *found_zval;
		total_to_sync++;
	}

	/* Now go through the extracted keys and sync the values to them */
	for (i = 0; i < total_to_sync; i++) {
		key_info = &copy_to_keys[i];

		/* Prepare zval to be synced to another key */
		Z_ADDREF_P(key_info->value);
		if (Z_ISREF_P(key_info->value)) {
			SEPARATE_ZVAL(&key_info->value);
		}

		/* Copy value to synced key */
		if (key_info->key_name == NULL) {
			zend_hash_index_update(ht_data, key_info->key_hash, &key_info->value, sizeof(zval *), NULL);
		} else {
			zend_hash_update(ht_data, key_info->key_name, key_info->key_name_len + 1, &key_info->value, sizeof(zval *), NULL);
		}
	}
}

/* protected function _construct() */
PHP_METHOD(Varien_Object, _construct)
{
}

/* Check whether key contains '/', and if true, then accept $key = 'a/b/c' as query for $this->_data['a']['b']['c'] */
int getData_fetch_by_path_key(zval *data, zval *key_param, zval *return_value TSRMLS_DC)
{
	/*
	---PHP---
    if (strpos($key,'/')) {
        $keyArr = explode('/', $key);
        $data = $this->_data;
        foreach ($keyArr as $i=>$k) {
            if ($k==='') {
                return $default;
            }
            if (is_array($data)) {
                if (!isset($data[$k])) {
                    return $default;
                }
                $data = $data[$k];
            } elseif ($data instanceof Varien_Object) {
                $data = $data->getData($k);
            } else {
                return $default;
            }
        }
        return $data;
    }
	*/

	zend_uchar key_param_type;
	char *key_str;
	uint key_str_len;
	char *found, *search_key, *current_key, *key_end;
	uint current_key_len;
	zval **current_zval;
	HashTable *ht;
	int result;
	zval *param_zval;
	zval *tmp_retval, *call_retval = NULL;

	#define RELEASE_CALL_RETVAL if (call_retval) {zval_ptr_dtor(&call_retval); call_retval = NULL;}

	/* Optimization - if key of basic value, then there is no '/' in it */
	key_param_type = key_param ? Z_TYPE_P(key_param) : IS_NULL;
	switch (key_param_type) {
		case IS_NULL:
		case IS_LONG:
		case IS_DOUBLE:
		case IS_BOOL:
			return FALSE;
	}

	/* Convert key param to string. Note: key_param is considered an independent variable. */
	convert_to_string(key_param);
	key_str = Z_STRVAL_P(key_param);
	key_str_len = Z_STRLEN_P(key_param);

	/* Algorithm */
	key_end = key_str + key_str_len - 1;
	found = php_memnstr(key_str, "/", 1, key_end);
	if (!found) {
		return FALSE;
	}
	
	current_key = key_str;
	current_key_len = found - current_key;
	current_zval = &data;
	do {
		if (!current_key_len) {
			RELEASE_CALL_RETVAL;
			RETVAL_NULL();
			return TRUE;
		}

		if (Z_TYPE_PP(current_zval) == IS_ARRAY) {
			ht = Z_ARRVAL_PP(current_zval);
			search_key = estrndup(current_key, current_key_len); /* So we have key with "\0" at end, which is needed for array hash */
			result = zend_symtable_find(ht, search_key, current_key_len + 1, (void **) &current_zval);
			efree(search_key);
			if (result == FAILURE) {
				RELEASE_CALL_RETVAL;
				RETVAL_NULL();
				return TRUE;
			}
		} else if ((Z_TYPE_PP(current_zval) == IS_OBJECT) && (instanceof_function(Z_OBJCE_PP(current_zval), vo_class TSRMLS_CC))) {
			ALLOC_INIT_ZVAL(param_zval);
			ZVAL_STRINGL(param_zval, current_key, current_key_len, TRUE);
			zend_call_method_with_1_params(current_zval, Z_OBJCE_PP(current_zval), NULL, "getdata", &tmp_retval, param_zval);
			zval_ptr_dtor(&param_zval);
			RELEASE_CALL_RETVAL;
			call_retval = tmp_retval;

			if (call_retval) {
				current_zval = &call_retval;
			} else {
				RETVAL_FALSE;
				return TRUE;
			}
		} else {
			RELEASE_CALL_RETVAL;
			RETVAL_NULL();
			return TRUE;
		}

		/* Prepare data for next iteration */
		current_key += current_key_len + 1;
		
		if (current_key == key_end + 1) {
			current_key_len = 0;
			continue;
		} else if (current_key > key_end + 1) {
			break;
		}

		found = php_memnstr(current_key, "/", 1, key_end);
		if (found) {
			current_key_len = found - current_key;
		} else {
			current_key_len = key_end - current_key + 1;
		}
	} while (1);

	MAKE_COPY_ZVAL(current_zval, return_value);
	RELEASE_CALL_RETVAL;
	return TRUE;

	#undef RELEASE_CALL_RETVAL
}


/* Get value by $key, and optionally, if $index is passed, then engage old index functionality */
int getData_fetch_by_key_and_index(zval *data, zval *key_param, zval *index_param, zval *return_value TSRMLS_DC)
{
	/*
	---PHP---
	if (isset($this->_data[$key])) {
		if (is_null($index)) {
			return $this->_data[$key];
		}

		$value = $this->_data[$key];
		if (is_array($value)) {
			if (isset($value[$index])) {
				return $value[$index];
			}
			return null;
		} elseif (is_string($value)) {
			$arr = explode("\n", $value);
			return (isset($arr[$index]) && (!empty($arr[$index]) || strlen($arr[$index]) > 0)) ? $arr[$index] : null;
		} elseif ($value instanceof Varien_Object) {
			return $value->getData($index);
		}
		return $default;
	}
	return $default;
	*/

	char *key_str, *index_str;
	uint key_str_len, index_str_len;
	long key_long, index_long;
	zend_bool is_dispose_key_str, is_dispose_index_str;
	HashTable *ht_data, *ht_value, *ht;
	zval **value, **index_val_pp;
	zval *index_val_p = NULL;
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	zval *explode, *eol, *exploded = NULL;
	zval **explode_params[2];

	/* Convert key param to string */
	expand_to_long_or_string_array_key(key_param, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);

	/* isset($this->_data[$key]) */
	ht_data = Z_ARRVAL_P(data);
	value = NULL;
	if (key_str) {
		zend_symtable_find(ht_data, key_str, key_str_len + 1, (void **) &value);
		if (is_dispose_key_str) {
			efree(key_str); 
		}
	} else {
		zend_hash_index_find(ht_data, key_long, (void **) &value);
	}

	if (!value) {
		RETVAL_NULL();
		return TRUE;
	}

	if (!index_param || (Z_TYPE_P(index_param) == IS_NULL)) {
		MAKE_COPY_ZVAL(value, return_value);
		return TRUE;
	}

	/* Depending on value - choose how to fetch data by index */
	/*---Value is array - get by index-------------------------*/
	if (Z_TYPE_PP(value) == IS_ARRAY) {
		ht_value = Z_ARRVAL_PP(value);
		expand_to_long_or_string_array_key(index_param, &index_long, &index_str, &index_str_len, &is_dispose_index_str TSRMLS_CC);
		index_val_pp = NULL;
		if (index_str) {
			zend_symtable_find(ht_value, index_str, index_str_len + 1, (void **) &index_val_pp);
		} else {
			zend_hash_index_find(ht_value, index_long, (void **) &index_val_pp);
		}
		if (is_dispose_index_str) {
			efree(index_str);
		}

		if (!index_val_pp) {
			RETVAL_NULL();
			return TRUE;
		}
		MAKE_COPY_ZVAL(index_val_pp, return_value);
		return TRUE;
	}

	/*---Value is string - explode it by "\n" and get by index-------------------*/
	if (Z_TYPE_PP(value) == IS_STRING) {
		if (!Z_STRLEN_PP(value)) {
			RETVAL_NULL();
			return TRUE;
		}

		MAKE_STD_ZVAL(explode);
		ZVAL_STRINGL(explode, "explode", sizeof("explode") - 1, 0);
		if (SUCCESS == zend_fcall_info_init(explode, 0, &fci, &fcc, NULL, NULL TSRMLS_CC)) {
			MAKE_STD_ZVAL(eol);
			ZVAL_STRINGL(eol, "\n", 1, 0);
			explode_params[0] = &eol;
			explode_params[1] = value;
			fci.param_count = 2;
			fci.params = explode_params;
			fci.retval_ptr_ptr = &exploded;
			
			zend_call_function(&fci, &fcc TSRMLS_CC);
			FREE_ZVAL(eol);

			if (exploded) {
				if (Z_TYPE_P(exploded) == IS_ARRAY) {
					ht = Z_ARRVAL_P(exploded);
					expand_to_long_or_string_array_key(index_param, &index_long, &index_str, &index_str_len, &is_dispose_index_str TSRMLS_CC);
					index_val_pp = NULL;
					if (index_str) {
						zend_symtable_find(ht, index_str, index_str_len + 1, (void **) &index_val_pp);
					} else {
						zend_hash_index_find(ht, index_long, (void **) &index_val_pp);
					}
					if (is_dispose_index_str) {
						efree(index_str);
					}

					if (index_val_pp) {
						MAKE_COPY_ZVAL(index_val_pp, return_value);
					} else {
						RETVAL_NULL();
					}
				} else {
					RETVAL_NULL();
				}
				zval_ptr_dtor(&exploded);
			}
		} else {
			RETVAL_NULL();
		}
		FREE_ZVAL(explode);

		return TRUE;
	}

	/*---Value is Varien_Object - get result by calling getData()-------------------*/
	if ((Z_TYPE_PP(value) == IS_OBJECT) && (instanceof_function(Z_OBJCE_PP(value), vo_class TSRMLS_CC))) {
		zend_call_method_with_1_params(value, Z_OBJCE_PP(value), NULL, "getdata", &index_val_p, index_param);
		if (index_val_p) {
			COPY_PZVAL_TO_ZVAL(*return_value, index_val_p);
		} else {
			RETVAL_FALSE;
		}
		return TRUE;
	}

	/*---Found something, which cannot be fetched by index-----------------*/
	RETVAL_NULL();
	return TRUE;
}

/*public function getData($key='', $index=null)*/
PHP_METHOD(Varien_Object, getData)
{
	zval *object = getThis();
	int num_args = ZEND_NUM_ARGS();
	zend_bool is_return_whole_data = FALSE;
	zval *key_param = NULL, *index_param = NULL;
	int parse_result;
	zval **data;

	if (!return_value_used) {
		return;
	}

	if (num_args) {
		if (num_args == 1) {
			parse_result = zend_parse_parameters(num_args TSRMLS_CC, "z!", &key_param);
		} else {
			parse_result = zend_parse_parameters(num_args TSRMLS_CC, "z!z!", &key_param, &index_param);
		}
		if (parse_result == FAILURE) {
			return;
		}
		is_return_whole_data = key_param && (Z_TYPE_P(key_param) == IS_STRING) && !Z_STRLEN_P(key_param);
	} else {
		is_return_whole_data = TRUE;
	}

	/* Process different cases what to return */
	VO_EXTRACT_PROPERTY(_data, object, &data);

	/* Whole data is requested */
	if (is_return_whole_data) {
		MAKE_COPY_ZVAL(data, return_value);
		return;
	} 
	
	/* Key passed contains '/' */
	if (getData_fetch_by_path_key(*data, key_param, return_value TSRMLS_CC)) {
		return;
	}

	/* Extract $this->_data[$key] */
	if (getData_fetch_by_key_and_index(*data, key_param, index_param, return_value TSRMLS_CC)) {
		return;
	}

	/* Nothing applicable found - just return NULL */
	RETURN_NULL();
}

/* public function setData($key, $value=null) */
PHP_METHOD(Varien_Object, setData)
{
	/* ---PHP---
    $this->_hasDataChanges = true;
    if(is_array($key)) {
        $this->_data = $key;
        $this->_addFullNames();
    } else {
        $this->_data[$key] = $value;
        if (isset($this->_syncFieldsMap[$key])) {
            $fullFieldName = $this->_syncFieldsMap[$key];
            $this->_data[$fullFieldName] = $value;
        }
    }
    return $this;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();

	zval *key_zval, *value_zval = NULL;
	long key_long;
	char *key_str;
	uint key_str_len;
	zend_bool is_dispose_key_str;
	
	zval **data;
	HashTable *ht_data;

	zval **syncFieldsMap;
	HashTable *ht_syncFieldsMap;
	zval **sync_to_key;
	int found_result;
	long sync_long;
	char *sync_str;
	uint sync_str_len;
	zend_bool is_dispose_sync_str;

	/* Raise _hasDataChanges */
	zend_update_property_bool(obj_ce, obj_zval, "_hasDataChanges", sizeof("_hasDataChanges") - 1, TRUE TSRMLS_CC);
	
	/* Process params */
	if (zend_parse_parameters(num_args TSRMLS_CC, "z|z", &key_zval, &value_zval) == FAILURE) {
		return;
	}

	if (Z_TYPE_P(key_zval) == IS_ARRAY) {
		zend_update_property(obj_ce, obj_zval, "_data", sizeof("_data") - 1, key_zval TSRMLS_CC);
		zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "_addfullnames", NULL);
	} else {
		VO_VERIFY_SCALAR_INCOMING_VALUE_P(key_zval, "key", return);

		/* Extract and check property */
		VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
		if (Z_TYPE_PP(data) != IS_ARRAY) {
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
		}
		SEPARATE_ZVAL_IF_NOT_REF(data);
		ht_data = Z_ARRVAL_PP(data);

		if (!value_zval) {
			ALLOC_INIT_ZVAL(value_zval);
			ZVAL_NULL(value_zval);
		} else {
			Z_ADDREF_P(value_zval);
		}

		expand_to_long_or_string_array_key(key_zval, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);
		if (key_str) {
			zend_symtable_update(ht_data, key_str, key_str_len + 1, &value_zval, sizeof(zval *), NULL);
		} else {
			zend_hash_index_update(ht_data, key_long, &value_zval, sizeof(zval *), NULL);
		}

		/* Sync value according to sync fields map */
		VO_EXTRACT_PROPERTY(_syncFieldsMap, obj_zval, &syncFieldsMap);
		if (!syncFieldsMap || !(*syncFieldsMap) || (Z_TYPE_PP(syncFieldsMap) != IS_ARRAY)) {
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "_syncFieldsMap property must be array");
		}
		ht_syncFieldsMap = Z_ARRVAL_PP(syncFieldsMap);

		/* Find the keys, to which we will sync values from _data. */
		if (key_str) {
			found_result = zend_symtable_find(ht_syncFieldsMap, key_str, key_str_len + 1, (void **) &sync_to_key);
		} else {
			found_result = zend_hash_index_find(ht_syncFieldsMap, key_long, (void **) &sync_to_key);
		}

		if (found_result == SUCCESS) {
			/* Put the value to the _data[sync_to_key] */
			Z_ADDREF_P(value_zval);

			expand_to_long_or_string_array_key(*sync_to_key, &sync_long, &sync_str, &sync_str_len, &is_dispose_sync_str TSRMLS_CC);
			if (sync_str) {
				zend_symtable_update(ht_data, sync_str, sync_str_len + 1, &value_zval, sizeof(zval *), NULL);
			} else {
				zend_hash_index_update(ht_data, sync_long, &value_zval, sizeof(zval *), NULL);
			}

			if (is_dispose_sync_str) {
				efree(sync_str);
			}
		}

		/* Free temp resources */
		if (is_dispose_key_str) {
			efree(key_str);
		}
	}

	/* Return $this */
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function hasDataChanges() */
PHP_METHOD(Varien_Object, hasDataChanges)
{
	/* ---PHP---
    return $this->_hasDataChanges;
	*/

	zval *obj_zval = getThis();
	zval **has_data_changes;

	if (return_value_used) {
		VO_EXTRACT_PROPERTY(_hasDataChanges, obj_zval, &has_data_changes);
		MAKE_COPY_ZVAL(has_data_changes, return_value);
	}
}

/* public function isDeleted($isDeleted=null) */
PHP_METHOD(Varien_Object, isDeleted)
{
	/* ---PHP---
    $result = $this->_isDeleted;
    if (!is_null($isDeleted)) {
        $this->_isDeleted = $isDeleted;
    }
    return $result;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *isDeleted;
	zval **old_isDeleted;

	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "z!", &isDeleted) == FAILURE) {
			return;
		}
	} else {
		isDeleted = NULL;
	}

	/* Fetch current value */
	if (return_value_used) {
		VO_EXTRACT_PROPERTY(_isDeleted, obj_zval, &old_isDeleted);
		MAKE_COPY_ZVAL(old_isDeleted, return_value);
	}

	/* Set new value, if requested */
	if (isDeleted != NULL) {
		zend_update_property(obj_ce, obj_zval, "_isDeleted", sizeof("_isDeleted") - 1, isDeleted TSRMLS_CC);
	}
}

/* public function setIdFieldName($name) */
PHP_METHOD(Varien_Object, setIdFieldName)
{
	/* ---PHP---
    $this->_idFieldName = $name;
    return $this;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *name;


	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &name) == FAILURE) {
		return;
	}

	zend_update_property(obj_ce, obj_zval, "_idFieldName", sizeof("_idFieldName") - 1, name TSRMLS_CC);

	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function getIdFieldName() */
PHP_METHOD(Varien_Object, getIdFieldName)
{
	/* ---PHP---
    return $this->_isFieldName;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval **idFieldName;

	if (return_value_used) {
		VO_EXTRACT_PROPERTY(_idFieldName, obj_zval, &idFieldName);
		MAKE_COPY_ZVAL(idFieldName, return_value);
	}
}

/* Return either custom id field name, or "id", which is default; or NULL in case of failure */
inline static zval *retrieve_copy_of_id_field_name(zval *obj_zval, zend_class_entry *obj_ce TSRMLS_DC)
{
	zval *id_field_name;

	zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "getidfieldname", &id_field_name);

	if (!id_field_name) {
		return NULL;
	}

	if (!i_zend_is_true(id_field_name)) {
		zval_ptr_dtor(&id_field_name);
		ALLOC_INIT_ZVAL(id_field_name);
		ZVAL_STRINGL(id_field_name, "id", sizeof("id") - 1, TRUE);
	}

	return id_field_name;
}

/* public function getId() */
PHP_METHOD(Varien_Object, getId)
{
	/* ---PHP---
	if ($this->getIdFieldName()) {
		return $this->_getData($this->getIdFieldName());
	}
	return $this->_getData('id');
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval *id_field_name;
	zval *id;

	if (!return_value_used) {
		return;
	}

	id_field_name = retrieve_copy_of_id_field_name(obj_zval, obj_ce TSRMLS_CC);
	if (!id_field_name) {
		RETURN_FALSE;
	}

	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "_getdata", &id, id_field_name);
	zval_ptr_dtor(&id_field_name);

	if (id) {
		COPY_PZVAL_TO_ZVAL(*return_value, id);
	} else {
		RETURN_FALSE;
	}
}

/* public function setId() */
PHP_METHOD(Varien_Object, setId)
{
	/* ---PHP---
	if ($this->getIdFieldName()) {
		$this->setData($this->getIdFieldName(), $value);
	} else {
		$this->setData('id', $value);
	}
	return $this;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *id_field_name = NULL;
	zval *value;

	/* Get passed $value */
	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &value) == FAILURE) {
		return;
	}

	/* Set $value to the 'id' field */
	id_field_name = retrieve_copy_of_id_field_name(obj_zval, obj_ce TSRMLS_CC);
	if (!id_field_name) {
		RETURN_FALSE;
	}

	zend_call_method_with_2_params(&obj_zval, obj_ce, NULL, "setdata", NULL, id_field_name, value);
	zval_ptr_dtor(&id_field_name);

	/* Return $this */
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function addData(array $arr) */
PHP_METHOD(Varien_Object, addData)
{
	/* ---PHP---
	foreach($arr as $index=>$value) {
		$this->setData($index, $value);
	}
	return $this;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	HashTable *ht_arr;
	int num;
	int current_key_type;
	ulong current_index;
	char *current_key;
	uint current_key_len;
	zval *index_zval, **value_zval;

	/* Get passed $arr */
	if (zend_parse_parameters(num_args TSRMLS_CC, "h", &ht_arr) == FAILURE) {
		return;
	}

	num = zend_hash_num_elements(ht_arr);
	if (num) {
		ALLOC_INIT_ZVAL(index_zval);
		for (zend_hash_internal_pointer_reset(ht_arr); zend_hash_has_more_elements(ht_arr) == SUCCESS; zend_hash_move_forward(ht_arr)) {
			current_key_type = zend_hash_get_current_key_ex(ht_arr, &current_key, &current_key_len, &current_index, FALSE, NULL);
			if (current_key_type == HASH_KEY_IS_LONG) {
				ZVAL_LONG(index_zval, current_index); 
			} else {
				ZVAL_STRINGL(index_zval, current_key, current_key_len - 1, FALSE); 
			}
			
			zend_hash_get_current_data(ht_arr, (void **) &value_zval);

			zend_call_method_with_2_params(&obj_zval, obj_ce, NULL, "setdata", NULL, index_zval, *value_zval);
		}
		/* Free memory. Check not to leave string value, because the string was not duplicated, so its memory doesn't belong to us. */
		if (Z_TYPE_P(index_zval) == IS_STRING) {
			ZVAL_NULL(index_zval);
		}
		zval_ptr_dtor(&index_zval);
	}

	/* Return $this */
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function unsetData($key=null) */
PHP_METHOD(Varien_Object, unsetData)
{
	/* ---PHP---
    $this->_hasDataChanges = true;
    if (is_null($key)) {
        $this->_data = array();
    } else {
        unset($this->_data[$key]);
        if (isset($this->_syncFieldsMap[$key])) {
            $fullFieldName = $this->_syncFieldsMap[$key];
            unset($this->_data[$fullFieldName]);
        }
    }
	return $this;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *key;
	HashTable *ht_data, *ht_sync;
	long key_long, field_long;
	char *key_str, *field_str;
	uint key_str_len, field_str_len;
	zend_bool is_dispose_key_str, is_dispose_field_str;
	zval **data;
	zval **fullFieldName;
	zval **syncFieldsMap;
	int sync_found_result;

	/* Raise _hasDataChanges */
	zend_update_property_bool(obj_ce, obj_zval, "_hasDataChanges", sizeof("_hasDataChanges") - 1, TRUE TSRMLS_CC);

	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "z!", &key) == FAILURE) {
			return;
		}
		VO_VERIFY_SCALAR_INCOMING_VALUE_P(key, "key", return);
	} else {
		key = NULL;
	}

	/* Fetch data property array */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}
	SEPARATE_ZVAL_IF_NOT_REF(data);
	ht_data = Z_ARRVAL_PP(data);

	if (key == NULL) {
		zend_hash_clean(ht_data);
	} else {
		expand_to_long_or_string_array_key(key, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);

		/* Unset appropriate data[$key] */
		if (key_str) {
			zend_symtable_del(ht_data, key_str, key_str_len + 1);
		} else {
			zend_hash_index_del(ht_data, key_long);
		}

		/* Unset the value from synced key, if any */
		VO_EXTRACT_PROPERTY(_syncFieldsMap, obj_zval, &syncFieldsMap);
		if (!syncFieldsMap || !(*syncFieldsMap) || (Z_TYPE_PP(syncFieldsMap) != IS_ARRAY)) {
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "_syncFieldsMap property must be array");
		}
		ht_sync = Z_ARRVAL_PP(syncFieldsMap);

		if (key_str) {
			sync_found_result = zend_symtable_find(ht_sync, key_str, key_str_len + 1, (void **) &fullFieldName);
		} else {
			sync_found_result = zend_hash_index_find(ht_sync, key_long, (void **) &fullFieldName);
		}

		if (sync_found_result == SUCCESS) {
			expand_to_long_or_string_array_key(*fullFieldName, &field_long, &field_str, &field_str_len, &is_dispose_field_str TSRMLS_CC);
			if (field_str) {
				zend_symtable_del(ht_data, field_str, field_str_len + 1);
			} else {
				zend_hash_index_del(ht_data, field_long);
			}
			if (is_dispose_field_str) {
				efree(field_str);
			}
		}

		/* Free resources */
		if (is_dispose_key_str) {
			efree(key_str);
		}
	}

	/* Return $this */
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function unsetOldData($key=null) */
PHP_METHOD(Varien_Object, unsetOldData)
{
	/* ---PHP---
    if (is_null($key)) {
        foreach ($this->_oldFieldsMap as $key => $newFieldName) {
            unset($this->_data[$key]);
        }
    } else {
        unset($this->_data[$key]);
    }
    return $this;
	*/

	/* Note: in Magento CE 1.8.1.0 a bug fixed in this code - instead of _syncFieldsMap, the _oldFieldsMap is used. 
	   This implementation is same as Magento CE 1.8.1.0. */
	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *key;
	HashTable *ht_data;
	long key_long;
	char *key_str;
	uint key_str_len;
	zend_bool is_dispose_key_str;
	zval **data;
	zval **oldFieldsMap;
	HashTable *ht_oldFieldsMap;
	int current_key_type;
	ulong current_index;
	char *current_key;
	uint current_key_len;

	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "z!", &key) == FAILURE) {
			return;
		}
		VO_VERIFY_SCALAR_INCOMING_VALUE_P(key, "key", return);
	} else {
		key = NULL;
	}

	/* Fetch data property array */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}
	SEPARATE_ZVAL_IF_NOT_REF(data);
	ht_data = Z_ARRVAL_PP(data);

	if ((key == NULL) && (zend_hash_num_elements(ht_data))) {
		VO_EXTRACT_PROPERTY(_oldFieldsMap, obj_zval, &oldFieldsMap);
		if (!oldFieldsMap || !(*oldFieldsMap) || (Z_TYPE_PP(oldFieldsMap) != IS_ARRAY)) {
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "_oldFieldsMap property must be array");
		}
		ht_oldFieldsMap = Z_ARRVAL_PP(oldFieldsMap);

		if (zend_hash_num_elements(ht_oldFieldsMap)) {
			for (zend_hash_internal_pointer_reset(ht_oldFieldsMap); zend_hash_has_more_elements(ht_oldFieldsMap) == SUCCESS; zend_hash_move_forward(ht_oldFieldsMap)) {
				current_key_type = zend_hash_get_current_key_ex(ht_oldFieldsMap, &current_key, &current_key_len, &current_index, FALSE, NULL);

				if (current_key_type == HASH_KEY_IS_LONG) {
					zend_hash_index_del(ht_data, current_index);
				} else {
					zend_symtable_del(ht_data, current_key, current_key_len);
				}
			}
		}
	} else {
		expand_to_long_or_string_array_key(key, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);

		if (key_str) {
			zend_symtable_del(ht_data, key_str, key_str_len + 1);
		} else {
			zend_hash_index_del(ht_data, key_long);
		}

		if (is_dispose_key_str) {
			efree(key_str);
		}
	}

	/* Return $this */
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* protected function _getData($key) */
PHP_METHOD(Varien_Object, _getData)
{
	/* ---PHP---
    return isset($this->_data[$key]) ? $this->_data[$key] : null;
	*/

	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval *key_zval;
	char *key_str;
	uint key_str_len;
	long key_long;
	zend_bool is_dispose_key_str;
	zval **data;
	HashTable *ht_data;
	zval **value;

	if (!return_value_used) {
		return;
	}

	/* Extract and check property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}
	ht_data = Z_ARRVAL_PP(data);

	/* Extract key to be searched by */
	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &key_zval) == FAILURE) {
		return;
	}
	VO_VERIFY_SCALAR_INCOMING_VALUE_P(key_zval, "key", return);

	/* Find value by the key */
	expand_to_long_or_string_array_key(key_zval, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);
	if (key_str) {
		if (zend_symtable_find(ht_data, key_str, key_str_len + 1, (void **) &value) == FAILURE) {
			value = NULL;
		}
	} else {
		if (zend_hash_index_find(ht_data, key_long, (void **) &value) == FAILURE) {
			value = NULL;
		}
	}
	if (is_dispose_key_str) {
		efree(key_str);
	}

	if (!value) {
		RETURN_NULL();
	}
	MAKE_COPY_ZVAL(value, return_value);
	
}

/* This func camelizes string (e.g. "abcd_ef" => "AbcdEf"). 
Prefix is not needed for actual camelization, just embedded here for optimization. */
static inline void vo_camelize(char *str, uint str_len, char *prefix, uint prefix_len, char **res, uint *res_len TSRMLS_DC)
{
	char symbol;
	uint i;
	char *result;
	uint result_len;
	zend_bool camelize_next;

	result = emalloc(prefix_len + str_len + 1);
	result_len = 0;

	/* Add prefix to result */
	if (prefix_len) {
		memcpy(result, prefix, prefix_len);
		result_len = prefix_len;
	}

	camelize_next = TRUE;
	for (i = 0; i < str_len; i++) {
		symbol = str[i];
		if (symbol == '_') {
			camelize_next = TRUE;
		} else {
			if (camelize_next) {
				if ((symbol >= 'a') && (symbol <= 'z')) {
					symbol += 'A' - 'a';
				}
				camelize_next = FALSE;
			}
			result[result_len++] = symbol;
		}
	}
	result[result_len] = '\0';

	*res = result;
	*res_len = result_len;
}

/* public function setDataUsingMethod($key, $args=array()) */
PHP_METHOD(Varien_Object, setDataUsingMethod)
{
	/* ---PHP---
    $method = 'set'.$this->_camelize($key);
    $this->$method($args);
    return $this;
	*/

	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	char *key;
	uint key_len;
	zval *args;
	HashTable *ht_args;
	zend_bool is_free_args;
	char *method;
	uint method_len;

	/* Extract parameters */
	args = NULL;
	if (zend_parse_parameters(num_args TSRMLS_CC, "s|z", &key, &key_len, &args) == FAILURE) {
		return;
	}

	is_free_args = 0;
	if (args == NULL) {
		is_free_args = 1;

		ALLOC_HASHTABLE(ht_args);
		if (zend_hash_init(ht_args, 0, NULL, ZVAL_PTR_DTOR, TRUE) == FAILURE) {
			FREE_HASHTABLE(ht_args);
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for $args variable");
		}

		MAKE_STD_ZVAL(args);
		Z_TYPE_P(args) = IS_ARRAY;
		Z_ARRVAL_P(args) = ht_args;
	}

	/* Compose name of the method to be used */
	vo_camelize(key, key_len, "set", sizeof("set") - 1, &method, &method_len TSRMLS_CC);

	/* Call the method */
	zend_call_method(&obj_zval, NULL, NULL, method, method_len, NULL, 1, args, NULL TSRMLS_CC);

	/* Free memory */
	efree(method);
	if (is_free_args) {
		zval_ptr_dtor(&args);
	}

	/* Return $this */
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function getDataUsingMethod($key, $args=null) */
PHP_METHOD(Varien_Object, getDataUsingMethod)
{
	/* ---PHP---
	$method = 'get'.$this->_camelize($key);
	return $this->$method($args);
	*/

	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	char *key;
	uint key_len;
	zval *args;
	zend_bool is_free_args;
	char *method;
	uint method_len;
	zval **retval_ptr_ptr;

	/* Extract parameters */
	args = NULL;
	if (zend_parse_parameters(num_args TSRMLS_CC, "s|z", &key, &key_len, &args) == FAILURE) {
		return;
	}

	is_free_args = 0;
	if (args == NULL) {
		is_free_args = 1;
		MAKE_STD_ZVAL(args);
		ZVAL_NULL(args);
	}

	/* Compose name of the method to be used */
	vo_camelize(key, key_len, "get", sizeof("get") - 1, &method, &method_len TSRMLS_CC);

	/* Call the method */
	retval_ptr_ptr = return_value_used ? emalloc(sizeof(zval *)) : NULL;
	zend_call_method(&obj_zval, NULL, NULL, method, method_len, retval_ptr_ptr, 1, args, NULL TSRMLS_CC);

	/* Free memory */
	efree(method);
	if (is_free_args) {
		zval_ptr_dtor(&args);
	}
	
	/* Return data */
	if (retval_ptr_ptr) {
		if (*retval_ptr_ptr) {
			COPY_PZVAL_TO_ZVAL(*return_value, *retval_ptr_ptr);
		} else {
			/* Failure in the called method */
			RETVAL_FALSE;
		}
		efree(retval_ptr_ptr);
	}	
}

/* public function getDataSetDefault($key, $default) */
PHP_METHOD(Varien_Object, getDataSetDefault)
{
	/* ---PHP---
	if (!isset($this->_data[$key])) {
		$this->_data[$key] = $default;
	}
	return $this->_data[$key];
	*/

	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	long key_long;
	char *key_str;
	uint key_str_len;
	zend_bool is_dispose_key_str;
	zval **data;
	HashTable *ht_data;
	zval **value;
	zval *key_param, *default_param;
	zend_bool is_set_default;

	if (zend_parse_parameters(num_args TSRMLS_CC, "zz", &key_param, &default_param) == FAILURE) {
		return;
	}
	VO_VERIFY_SCALAR_INCOMING_VALUE_P(key_param, "key", return);

	/* Extract and check _data property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}
	ht_data = Z_ARRVAL_PP(data);

	/* Convert key param to be used as long or string value */
	expand_to_long_or_string_array_key(key_param, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);

	/* Method body */
	is_set_default = TRUE;

	value = NULL;
	if (key_str) {
		if (zend_symtable_find(ht_data, key_str, key_str_len + 1, (void **) &value) == FAILURE) {
			value = NULL;
		}
	} else {
		if (zend_hash_index_find(ht_data, key_long, (void **) &value) == FAILURE) {
			value = NULL;
		}
	}

	if (value && (Z_TYPE_PP(value) != IS_NULL)) {
		is_set_default = FALSE;
	}

	if (is_set_default) {
		SEPARATE_ZVAL_IF_NOT_REF(data);
		ht_data = Z_ARRVAL_PP(data);
		SEPARATE_ARG_IF_REF(default_param);
		if (key_str) {
			zend_symtable_update(ht_data, key_str, key_str_len + 1, &default_param, sizeof(zval *), NULL);
		} else {
			zend_hash_index_update(ht_data, key_long, &default_param, sizeof(zval *), NULL);
		}
		if (return_value_used) {
			MAKE_COPY_ZVAL(&default_param, return_value);
		}
	} else if (return_value_used) {
		MAKE_COPY_ZVAL(value, return_value);
	}

	/* Dispose resources */
	if (is_dispose_key_str) {
		efree(key_str);
	}
}

/* public function hasData($key='') */
PHP_METHOD(Varien_Object, hasData)
{
	/* ---PHP---
	if (empty($key) || !is_string($key)) {
		return !empty($this->_data);
	}
	return array_key_exists($key, $this->_data);
	*/

	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval **data;
	HashTable *ht_data;
	zval *key;
	zend_bool is_return_data;

	if (!return_value_used) {
		return;
	}

	/* Extract and check _data property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}
	ht_data = Z_ARRVAL_PP(data);

	/* Parse parameter and decide, whether we should just return _data */
	is_return_data = TRUE;
	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "z!", &key) == FAILURE) {
			return;
		}
		if (key && (Z_TYPE_P(key) == IS_STRING) && Z_STRLEN_P(key)) {
			is_return_data = FALSE;
		}
	}

	RETURN_BOOL(is_return_data ? zend_hash_num_elements(ht_data) : zend_symtable_exists(ht_data, Z_STRVAL_P(key), Z_STRLEN_P(key) + 1));
}

/* public function __toArray(array $arrAttributes = array()) */
PHP_METHOD(Varien_Object, __toArray)
{
	/* ---PHP---
	if (empty($arrAttributes)) {
		return $this->_data;
	}

	$arrRes = array();
	foreach ($arrAttributes as $attribute) {
		if (isset($this->_data[$attribute])) {
			$arrRes[$attribute] = $this->_data[$attribute];
		}
		else {
			$arrRes[$attribute] = null;
		}
	}
	return $arrRes;	
	*/

	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval **data;
	HashTable *ht_data;
	zval *arrAttributes;
	HashTable *ht_arrAttributes;
	int num_attributes;
	HashTable *ht_arrRes;
	zval *null_zval;
	zval **found_zval, **attr_name;
	zval *copied_zval;
	int found_result;
	long attr_long;
	char *attr_str;
	uint attr_str_len;
	zend_bool is_dispose_attr_str;

	if (!return_value_used) {
		return;
	}

	/* Extract and check _data property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}

	/* Parse parameter and decide, whether we should just return _data */
	num_attributes = 0;
	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "a", &arrAttributes) == FAILURE) {
			return;
		}
		num_attributes = zend_hash_num_elements(Z_ARRVAL_P(arrAttributes));
	}

	/* Return just _data */
	if (!num_attributes) {
		MAKE_COPY_ZVAL(data, return_value);
		return;
	}

	/* Go through $arrAttributes and compose resulting array */
	array_init(return_value);
	ht_arrRes = Z_ARRVAL_P(return_value);
	ht_arrAttributes = Z_ARRVAL_P(arrAttributes);
	ht_data = Z_ARRVAL_PP(data);
	null_zval = NULL;
	for (zend_hash_internal_pointer_reset(ht_arrAttributes); zend_hash_has_more_elements(ht_arrAttributes) == SUCCESS; zend_hash_move_forward(ht_arrAttributes)) {
		/* Extract current value, i.e. name of the attribute to copy from */
		zend_hash_get_current_data(ht_arrAttributes, (void **) &attr_name);
		VO_VERIFY_SCALAR_INCOMING_VALUE_P(*attr_name, "in $arrAttributes", return);
		expand_to_long_or_string_array_key(*attr_name, &attr_long, &attr_str, &attr_str_len, &is_dispose_attr_str TSRMLS_CC);

		if (attr_str) {
			found_result = zend_symtable_find(ht_data, attr_str, attr_str_len + 1, (void **) &found_zval);
		} else {
			found_result = zend_hash_index_find(ht_data, attr_long, (void **) &found_zval);
		}

		if (found_result == SUCCESS) {
			copied_zval = *found_zval;
			SEPARATE_ARG_IF_REF(copied_zval);
		} else {
			if (null_zval) {
				Z_ADDREF_P(null_zval);
			} else {
				MAKE_STD_ZVAL(null_zval);
				ZVAL_NULL(null_zval);
			}
			copied_zval = null_zval;
		}

		/* Copy attribute to resulting array */
		if (attr_str) {
			zend_symtable_update(ht_arrRes, attr_str, attr_str_len + 1, &copied_zval, sizeof(zval *), NULL);
		} else {
			zend_hash_index_update(ht_arrRes, attr_long, &copied_zval, sizeof(zval *), NULL);
		}

		if (is_dispose_attr_str) {
			efree(attr_str);
		}
	}
}

/* public function toArray(array $arrAttributes = array()) */
PHP_METHOD(Varien_Object, toArray)
{
	/* ---PHP---
	return $this->__toArray($arrAttributes);
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *arrAttributes;
	zval *result;
	zend_bool arrAttributes_dispose;

	if (!return_value_used) {
		return;
	}

	/* Parse parameter and decide, whether we should just return _data */
	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "a", &arrAttributes) == FAILURE) {
			return;
		}
		arrAttributes_dispose = FALSE;
	} else {
		MAKE_STD_ZVAL(arrAttributes);
		array_init(arrAttributes);
		arrAttributes_dispose = TRUE;
	}

	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "__toarray", &result, arrAttributes);
	if (arrAttributes_dispose) {
		zval_ptr_dtor(&arrAttributes);
	}

	if (result) {
		COPY_PZVAL_TO_ZVAL(*return_value, result);
	} else {
		RETVAL_FALSE;
	}
}

/* protected function _prepareArray(&$arr, array $elements=array()) */
PHP_METHOD(Varien_Object, _prepareArray)
{
	/* ---PHP---
	foreach ($elements as $element) {
		if (!isset($arr[$element])) {
			$arr[$element] = null;
		}
	}
	return $arr;
	*/

	int num_args = ZEND_NUM_ARGS();
	zval *arr;
	zval **element;
	HashTable *ht_elements, *ht_arr;
	zval *null_zval;
	int element_exists;
	long element_long;
	char *element_str;
	uint element_str_len;
	zend_bool is_dispose_element_str;

	if (zend_parse_parameters(num_args TSRMLS_CC, "z|h", &arr, &ht_elements) == FAILURE) {
		return;
	}

	/* Process $elements */
	if ((num_args < 2) || !zend_hash_num_elements(ht_elements)) {
		if (return_value_used) {
			MAKE_COPY_ZVAL(&arr, return_value);
			return;
		}
	}

	/* Process $arr */
	if (Z_TYPE_P(arr) != IS_ARRAY) {
		if (Z_TYPE_P(arr) == IS_NULL) {
			array_init(arr);
		} else {
			php_error_docref(NULL TSRMLS_CC, E_WARNING, "$arr must be passed as array or null");
			RETURN_FALSE;
		}
	}
	ht_arr = Z_ARRVAL_P(arr);

	/* Fill $arr with nulls according to $elements */
	null_zval = NULL;
	for (zend_hash_internal_pointer_reset(ht_elements); zend_hash_has_more_elements(ht_elements) == SUCCESS; zend_hash_move_forward(ht_elements)) {
		/* Extract current value, i.e. element of $arr to fill in */
		zend_hash_get_current_data(ht_elements, (void **) &element);
		expand_to_long_or_string_array_key(*element, &element_long, &element_str, &element_str_len, &is_dispose_element_str TSRMLS_CC);

		if (element_str) {
			element_exists = zend_symtable_exists(ht_arr, element_str, element_str_len + 1);
		} else {
			element_exists = zend_hash_index_exists(ht_arr, element_long);
		}

		if (!element_exists) {
			if (null_zval) {
				Z_ADDREF_P(null_zval);
			} else {
				MAKE_STD_ZVAL(null_zval);
				ZVAL_NULL(null_zval);
			}
			if (element_str) {
				zend_symtable_update(ht_arr, element_str, element_str_len + 1, &null_zval, sizeof(zval *), NULL);
			} else {
				zend_hash_index_update(ht_arr, element_long, &null_zval, sizeof(zval *), NULL);
			}
		}

		if (is_dispose_element_str) {
			efree(element_str);
		}
	}

	if (return_value_used) {
		MAKE_COPY_ZVAL(&arr, return_value);
	}
}

/* protected function __toXml(array $arrAttributes = array(), $rootName = 'item', $addOpenTag=false, $addCdata=true) */
PHP_METHOD(Varien_Object, __toXml)
{
	/* ---PHP---
	$xml = '';
	if ($addOpenTag) {
		$xml.= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	}
	if (!empty($rootName)) {
		$xml.= '<'.$rootName.'>'."\n";
	}
	$xmlModel = new Varien_Simplexml_Element('<node></node>');
	$arrData = $this->toArray($arrAttributes);
	foreach ($arrData as $fieldName => $fieldValue) {
		if ($addCdata === true) {
			$fieldValue = "<![CDATA[$fieldValue]]>";
		} else {
			$fieldValue = $xmlModel->xmlentities($fieldValue);
		}
		$xml.= "<$fieldName>$fieldValue</$fieldName>"."\n";
	}
	if (!empty($rootName)) {
		$xml.= '</'.$rootName.'>'."\n";
	}
	return $xml;
	*/

	#define XML_START "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
	#define CDATA_START "<![CDATA["
	#define CDATA_END "]]>"
	#define EXPECTED_FIELD_NAME_LEN 15
	#define EXPECTED_FIELD_VAL_LEN 20
	#define LONG_BUFFER_SIZE 40 /* Just any number of chars to hold long value */

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *arrAttributes = NULL, *addCdata = NULL;
	char *rootName = NULL;
	uint rootName_len;
	zend_bool addOpenTag = FALSE;
	smart_str result = {0};
	size_t expected_len;
	size_t newlen; /* For smart_str_alloc() */
	zval *arrData;
	HashTable *ht_arrData;
	zend_bool is_add_cdata, is_dispose_arrAtrributes;
	zval **fieldValue;
	int fieldName_key_type;
	ulong fieldName_long;
	char *fieldName_str;
	uint fieldName_str_len;
	char *str_temp;
	char *fieldValue_cleaned_str;
	uint fieldValue_cleaned_len;
	zval *fieldValue_converted;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "|as!bz!", &arrAttributes, &rootName, &rootName_len, &addOpenTag, &addCdata) == FAILURE) {
		return;
	}

	/* Defaults */
	if (!rootName && (num_args < 2)) {
		rootName = "item";
		rootName_len = 4;
	}
	is_add_cdata = !addCdata || ((Z_TYPE_P(addCdata) == IS_BOOL) && Z_BVAL_P(addCdata));

	/* Fetch arrData */
	is_dispose_arrAtrributes = FALSE;
	if (!arrAttributes) {
		MAKE_STD_ZVAL(arrAttributes);
		array_init(arrAttributes);
		is_dispose_arrAtrributes = TRUE;
	}
	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "toarray", &arrData, arrAttributes);
	if (is_dispose_arrAtrributes) {
		zval_ptr_dtor(&arrAttributes);
	}
	if (!arrData) {
		RETURN_FALSE;
	}
	if (Z_TYPE_P(arrData) != IS_ARRAY) {
		zval_ptr_dtor(&arrData);
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "__toXml() expects toArray() to return array");
		RETURN_FALSE;
	}
	ht_arrData = Z_ARRVAL_P(arrData);

	/* Calculate expected length and pre-allocate buffer */
	expected_len = addOpenTag ? sizeof(XML_START) - 1 : 0;
	expected_len += rootName ? 3 + 4 + 2 * rootName_len : 0;
	expected_len += zend_hash_num_elements(ht_arrData) 
		* ((is_add_cdata ? sizeof(CDATA_START) - 1 + sizeof(CDATA_END) - 1 : 0) + EXPECTED_FIELD_NAME_LEN + EXPECTED_FIELD_VAL_LEN + 6); 
	smart_str_alloc(&result, expected_len, 0);

	/* Open tag */
	if (addOpenTag) {
		smart_str_appendl(&result, XML_START, sizeof(XML_START) - 1);
	}
	
	/* Root name */
	if (rootName && rootName_len) {
		smart_str_appendl(&result, "<", 1);
		smart_str_appendl(&result, rootName, rootName_len);
		smart_str_appendl(&result, ">\n", 2);
	}

	/* Compose body of XML */
	ALLOC_INIT_ZVAL(fieldValue_converted);
	str_temp = emalloc(LONG_BUFFER_SIZE);
	for (zend_hash_internal_pointer_reset(ht_arrData); zend_hash_has_more_elements(ht_arrData) == SUCCESS; zend_hash_move_forward(ht_arrData)) {
		zend_hash_get_current_data(ht_arrData, (void **) &fieldValue);
		fieldName_key_type = zend_hash_get_current_key_ex(ht_arrData, &fieldName_str, &fieldName_str_len, &fieldName_long, FALSE, NULL);
		
		if (fieldName_key_type == HASH_KEY_IS_LONG) {
			fieldName_str_len = snprintf(str_temp, LONG_BUFFER_SIZE, "%ld", fieldName_long);
			fieldName_str = str_temp;
		} else {
			fieldName_str_len--; /* To be properly used at the appending part */
		}

		/* Add node to resulting XML */
		smart_str_appendl(&result, "<", 1);
		smart_str_appendl(&result, fieldName_str, fieldName_str_len);
		smart_str_appendl(&result, ">", 1);

		if (is_add_cdata) {
			smart_str_appendl(&result, CDATA_START, sizeof(CDATA_START) - 1);
		}

		MAKE_COPY_ZVAL(fieldValue, fieldValue_converted);
		convert_to_string(fieldValue_converted);

		if (is_add_cdata) {
			smart_str_appendl(&result, Z_STRVAL_P(fieldValue_converted), Z_STRLEN_P(fieldValue_converted));
		} else {
			vo_convert_htmlentities(fieldValue_converted, &fieldValue_cleaned_str, &fieldValue_cleaned_len);
			smart_str_appendl(&result, fieldValue_cleaned_str, fieldValue_cleaned_len);
			efree(fieldValue_cleaned_str);
		}
		zval_dtor(fieldValue_converted);

		if (is_add_cdata) {
			smart_str_appendl(&result, CDATA_END, sizeof(CDATA_END) - 1);
		}

		smart_str_appendl(&result, "</", 2);
		smart_str_appendl(&result, fieldName_str, fieldName_str_len);
		smart_str_appendl(&result, ">\n", 2);
	}
	efree(fieldValue_converted);
	efree(str_temp);

	/* Ending root name */
	if (rootName && rootName_len) {
		smart_str_appendl(&result, "</", 2);
		smart_str_appendl(&result, rootName, rootName_len);
		smart_str_appendl(&result, ">\n", 2);
	}

	/* Result and free resources */
	smart_str_0(&result);
	RETVAL_STRINGL(result.c, result.len, 0);
	zval_ptr_dtor(&arrData);

	#undef XML_START
	#undef CDATA_START
	#undef CDATA_END
	#undef EXPECTED_FIELD_NAME_LEN
	#undef EXPECTED_FIELD_VAL_LEN
	#undef LONG_BUFFER_SIZE
}

static void vo_convert_htmlentities(zval * value, char **result_str, uint *result_len)
{
	typedef struct {
		char *from;
		uint from_len;
		char *to;
		uint to_len;
	} from_to;
	from_to replacement[5] = {
		{"&", 1, "&amp;", 5},
		{"\"", 1, "&quot;", 6},
		{"'", 1, "&apos;", 6},
		{"<", 1, "&lt;", 4},
		{">", 1, "&gt;", 4},
	};
	char *haystack;
	uint haystack_len;


	int i;
	haystack = Z_STRVAL_P(value);
	haystack_len = Z_STRLEN_P(value);
	for (i = 0; i < 5; i++) {
		*result_str = php_str_to_str_ex(haystack, haystack_len, replacement[i].from, replacement[i].from_len, replacement[i].to, replacement[i].to_len, result_len, 0, NULL);
		if (i) {
			efree(haystack); /* Was allocated at previous iteration */
		}
		haystack = *result_str;
		haystack_len = *result_len;
	}
}

/* public function toXml(array $arrAttributes = array(), $rootName = 'item', $addOpenTag=false, $addCdata=true) */
PHP_METHOD(Varien_Object, toXml)
{
	/* ---PHP---
	return $this->__toXml($arrAttributes, $rootName, $addOpenTag, $addCdata);
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *arrAttributes = NULL, *rootName = NULL, *addOpenTag = NULL, *addCdata = NULL;
	zend_bool arrAttributes_dispose, rootName_dispose, addOpenTag_dispose, addCdata_dispose;
	zval *result;
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	zval **params[4];
	int call_result;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "|azzz", &arrAttributes, &rootName, &addOpenTag, &addCdata) == FAILURE) {
		return;
	}

	/* Defaults */
	if (!arrAttributes) {
		MAKE_STD_ZVAL(arrAttributes);
		array_init(arrAttributes);
		arrAttributes_dispose = TRUE;
	} else {
		arrAttributes_dispose = FALSE;
	}

	if (!rootName) {
		MAKE_STD_ZVAL(rootName);
		ZVAL_STRINGL(rootName, "item", 4, 1);
		rootName_dispose = TRUE;
	} else {
		rootName_dispose = FALSE;
	}

	if (!addOpenTag) {
		MAKE_STD_ZVAL(addOpenTag);
		ZVAL_BOOL(addOpenTag, FALSE);
		addOpenTag_dispose = TRUE;
	} else {
		addOpenTag_dispose = FALSE;
	}

	if (!addCdata) {
		MAKE_STD_ZVAL(addCdata);
		ZVAL_BOOL(addCdata, TRUE);
		addCdata_dispose = TRUE;
	} else {
		addCdata_dispose = FALSE;
	}

	/* Call __toXml() */
	params[0] = &arrAttributes;
	params[1] = &rootName;
	params[2] = &addOpenTag;
	params[3] = &addCdata;

	fci.size = sizeof(fci);
	fci.object_ptr = obj_zval;
	fci.function_name = NULL;
	fci.retval_ptr_ptr = &result;
	fci.param_count = 4;
	fci.params = params;
	fci.no_separation = 1;
	fci.symbol_table = NULL;

	fcc.initialized = 1;
	zend_hash_find(&obj_ce->function_table, "__toxml", sizeof("__toxml"), (void **) &fcc.function_handler);
	fcc.calling_scope = obj_ce;
	fcc.called_scope = obj_ce;
	fcc.object_ptr = obj_zval;

	call_result = zend_call_function(&fci, &fcc TSRMLS_CC);

	if (arrAttributes_dispose) {
		zval_ptr_dtor(&arrAttributes);
	}
	if (rootName_dispose) {
		zval_ptr_dtor(&rootName);
	}
	if (addOpenTag_dispose) {
		zval_ptr_dtor(&addOpenTag);
	}
	if (addCdata_dispose) {
		zval_ptr_dtor(&addCdata);
	}

	/* Process result */
	if (call_result == FAILURE) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "Error while calling __toXml() method through toXml()");
	}
	if (!result) {
		RETURN_FALSE;
	}

	COPY_PZVAL_TO_ZVAL(*return_value, result);
}

/* protected function __toJson(array $arrAttributes = array()) */
PHP_METHOD(Varien_Object, __toJson)
{
	/* ---PHP---
	$arrData = $this->toArray($arrAttributes);
	$json = Zend_Json::encode($arrData);
	return $json;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zend_class_entry **zend_ce;
	int num_args = ZEND_NUM_ARGS();
	zval *arrAttributes = NULL;
	zend_bool arrAttributes_dispose;
	zval *arrData;
	zval *json;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "|a", &arrAttributes) == FAILURE) {
		return;
	}

	/* Fetch arrData */
	arrAttributes_dispose = FALSE;
	if (!arrAttributes) {
		MAKE_STD_ZVAL(arrAttributes);
		array_init(arrAttributes);
		arrAttributes_dispose = TRUE;
	}
	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "toarray", &arrData, arrAttributes);
	if (arrAttributes_dispose) {
		zval_ptr_dtor(&arrAttributes);
	}
	if (!arrData) {
		RETURN_FALSE;
	}
	if (Z_TYPE_P(arrData) != IS_ARRAY) {
		zval_ptr_dtor(&arrData);
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "__toJson() expects toArray() to return array");
		RETURN_FALSE;
	}

	/* Encode */
	if (zend_lookup_class("Zend_Json", sizeof("Zend_Json") - 1, &zend_ce TSRMLS_CC) == FAILURE) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "Class Zend_Json, needed for json encoding, is not found");
	}
	zend_call_method_with_1_params(NULL, *zend_ce, NULL, "encode", &json, arrData);

	zval_ptr_dtor(&arrData);

	/* Result */
	if (!json) {
		RETURN_FALSE;
	}
	COPY_PZVAL_TO_ZVAL(*return_value, json);
}

/* public function toJson(array $arrAttributes = array()) */
PHP_METHOD(Varien_Object, toJson)
{
	/* ---PHP---
	return $this->__toJson($arrAttributes);
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *arrAttributes = NULL;
	zend_bool arrAttributes_dispose;
	zval *result;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "|a", &arrAttributes) == FAILURE) {
		return;
	}

	/* Fetch arrData */
	arrAttributes_dispose = FALSE;
	if (!arrAttributes) {
		MAKE_STD_ZVAL(arrAttributes);
		array_init(arrAttributes);
		arrAttributes_dispose = TRUE;
	}
	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "__tojson", &result, arrAttributes);
	if (arrAttributes_dispose) {
		zval_ptr_dtor(&arrAttributes);
	}

	if (!result) {
		RETURN_FALSE;
	}

	COPY_PZVAL_TO_ZVAL(*return_value, result);
}

/* Concat _data by comma */
static void vo_toString_csv(zval *obj, zend_class_entry *obj_ce, zval *return_value TSRMLS_DC)
{
	/* ---PHP---
	$str = implode(', ', $this->getData());
	*/

	zval *data;
	HashTable *ht_data;
	smart_str result = {0};
	size_t newlen; /* For smart_str_alloc() */
	zval **fieldValue;
	zval *tmp_string = NULL;
	zend_bool is_first;

	zend_call_method_with_0_params(&obj, obj_ce, NULL, "getdata", &data);
	if (!data) {
		RETURN_FALSE;
	}
	if (Z_TYPE_P(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "getData() must return array for toString() method");
		RETURN_FALSE;
	}
	ht_data = Z_ARRVAL_P(data);

	smart_str_alloc(&result, zend_hash_num_elements(ht_data) * 15, 0); /* Estimate, that each value has ~13 symbols + 2 symbols for ", " */

	for (zend_hash_internal_pointer_reset(ht_data), is_first = TRUE; zend_hash_has_more_elements(ht_data) == SUCCESS; zend_hash_move_forward(ht_data), is_first = FALSE) {
		zend_hash_get_current_data(ht_data, (void **) &fieldValue);

		if (!is_first) {
			smart_str_appendl(&result, ", ", sizeof(", ") - 1);
		}

		if (Z_TYPE_PP(fieldValue) == IS_STRING) {
			smart_str_appendl(&result, Z_STRVAL_PP(fieldValue), Z_STRLEN_PP(fieldValue));
		} else {
			if (!tmp_string) {
				ALLOC_ZVAL(tmp_string);
			}
			MAKE_COPY_ZVAL(fieldValue, tmp_string);
			convert_to_string(tmp_string);
			smart_str_appendl(&result, Z_STRVAL_P(tmp_string), Z_STRLEN_P(tmp_string));
		}
	}

	/* Result and free resources */
	smart_str_0(&result);
	RETVAL_STRINGL(result.c, result.len, 0);
	zval_ptr_dtor(&data);
	if (tmp_string) {
		FREE_ZVAL(tmp_string);
	}
}

static zend_bool vo_toString_extract_var(char *str, uint len, char **var, uint *var_len)
{
	#define BEFORE_BRACES 1
	#define OPENED_BRACE1 2
	#define OPENED_BRACE2 3
	#define INSIDE_BRACES 4
	#define CLOSED_BRACE1 5
	#define CLOSED_BRACE2 6

	int state;
	char *current, *max_current;
	char current_char;

	max_current = str + len;
	for (state = BEFORE_BRACES, current = str; (state != CLOSED_BRACE2) && (current <= max_current); current++) {
		switch (state) {
			case BEFORE_BRACES:
				if (current[0] == '{') {
					state = OPENED_BRACE1;
				}
				break;
			case OPENED_BRACE1:
				if (current[0] == '{') {
					state = OPENED_BRACE2;
				} else {
					state = BEFORE_BRACES;
				}
				break;
			case OPENED_BRACE2:
				if (current[0] == '}') {
					state = BEFORE_BRACES;
					break;
				}
				*var = current;
				state = INSIDE_BRACES;
				/* break is absent intentionally */
			case INSIDE_BRACES:
				current_char = current[0];
				if (current_char == '}') {
					*var_len = current - *var;
					state = CLOSED_BRACE1;
				} else if (!(
					((current_char >= 'a') && (current_char <= 'z')) 
					|| ((current_char >= 'A') && (current_char <= 'Z')) 
					|| ((current_char >= '0') && (current_char <= '9')) 
					|| (current_char == '_')
				)) {
					state = BEFORE_BRACES;
				}
				break;
			case CLOSED_BRACE1:
				if (current[0] == '}') {
					state = CLOSED_BRACE2;
				} else {
					state = BEFORE_BRACES;
				}
				break;
		}
	}

	if (state == CLOSED_BRACE2) {
		return TRUE;
	} else {
		return FALSE;
	}

	#undef BEFORE_BRACES
	#undef OPENED_BRACE1
	#undef OPENED_BRACE2
	#undef INSIDE_BRACES
	#undef CLOSED_BRACE1
	#undef CLOSED_BRACE2
}

/* Fill the placeholders (e.g. {{var}}) in format, and return as return_value */
static void vo_toString_by_format(zval *obj_zval, zend_class_entry *obj_ce, zval *format_zval, zval *return_value TSRMLS_DC)
{
	smart_str result = {0};
	zval *tmp_format = NULL;
	char *format, *current, *var;
	uint format_len, current_len, var_len, portion_len;
	size_t newlen; /* For smart_str_alloc() */
	zval *getData_arg;
	zval *tmp_retval;

	#define FREE_TOSTRING_RESOURCES {				\
		if (getData_arg) {							\
			zval_ptr_dtor(&getData_arg);			\
		}											\
		if (tmp_format) {							\
			zval_ptr_dtor(&tmp_format);				\
		}											\
	}


	/* ---PHP---
	preg_match_all('/\{\{([a-z0-9_]+)\}\}/is', $format, $matches);
	foreach ($matches[1] as $var) {
		$format = str_replace('{{'.$var.'}}', $this->getData($var), $format);
	}
	$str = $format;
	*/

	/* Convert format to string to work with */
	if (Z_TYPE_P(format_zval) == IS_STRING) {
		format = Z_STRVAL_P(format_zval);
		format_len = Z_STRLEN_P(format_zval);
	} else {
		ALLOC_ZVAL(tmp_format);
		MAKE_COPY_ZVAL(&format_zval, tmp_format);
		convert_to_string(tmp_format);
		format = Z_STRVAL_P(tmp_format);
		format_len = Z_STRLEN_P(tmp_format);
	}

	/* Edge case, when the length is so small, that there are definitely no placeholders. Minimal placeholder has 5 symbols, e.g. "{{a}}" */
	if (format_len < 5) {
		if (tmp_format) {
			RETVAL_ZVAL(tmp_format, FALSE, FALSE);
			FREE_ZVAL(tmp_format);
		} else {
			MAKE_COPY_ZVAL(&format_zval, return_value);
		}
		return;
	}

	/* Allocate memory for result */
	smart_str_alloc(&result, format_len + 10 * 10, 0); /* Estimate, that there are 10 placeholders, and each replacement will increase length by 10 symbols */

	/* Go through the format string and replace all the placeholders in the form of {{var}} */
	ALLOC_INIT_ZVAL(getData_arg);
	ZVAL_NULL(getData_arg);
	for (
		current = format, current_len = format_len; 
		(current_len >= 5) && vo_toString_extract_var(current, current_len, &var, &var_len); /* 5 is the length of a minimal placeholder */
		current = var + var_len + 2, current_len = format_len - (current - format) /* 2 is the number of curly braces after the variable */
	) {
		/* Add portion before placeholder var */
		portion_len = var - current - 2; /* 2 is the number of curly braces before var */
		if (portion_len) {
			smart_str_appendl(&result, current, portion_len);
		}

		/* Free resources from previous iteration */
		if (Z_TYPE_P(getData_arg) == IS_STRING) {
			efree(Z_STRVAL_P(getData_arg));
		}

		/* Call getData() */
		ZVAL_STRINGL(getData_arg, var, var_len, TRUE); /* Duplicate in order to add NULL to the end */
		zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "getdata", &tmp_retval, getData_arg);
		if (!tmp_retval) {
			FREE_TOSTRING_RESOURCES;
			RETURN_FALSE;
		}

		/* Extract string */
		if (Z_TYPE_P(tmp_retval) != IS_STRING) {
			convert_to_string(tmp_retval);
		}
		smart_str_appendl(&result, Z_STRVAL_P(tmp_retval), Z_STRLEN_P(tmp_retval));
		zval_ptr_dtor(&tmp_retval);
	}

	/* Append whatever is left from going through the format string */
	if (current_len) {
		smart_str_appendl(&result, current, current_len);
	}

	/* Result */
	smart_str_0(&result);
	RETVAL_STRINGL(result.c, result.len, FALSE);

	/* Free memory */
	FREE_TOSTRING_RESOURCES;

	#undef FREE_TOSTRING_RESOURCES
}


/* public function toString($format='') */
PHP_METHOD(Varien_Object, toString)
{
	/* ---PHP---
	if (empty($format)) {
		$str = implode(', ', $this->getData());
	} else {
		preg_match_all('/\{\{([a-z0-9_]+)\}\}/is', $format, $matches);
		foreach ($matches[1] as $var) {
			$format = str_replace('{{'.$var.'}}', $this->getData($var), $format);
		}
		$str = $format;
	}
	return $str;
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *format = NULL;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "|z!", &format) == FAILURE) {
		return;
	}

	if (!format || !i_zend_is_true(format)) {
		vo_toString_csv(obj_zval, obj_ce, return_value TSRMLS_CC);
	} else {
		vo_toString_by_format(obj_zval, obj_ce, format, return_value TSRMLS_CC);
	}
}

/* Lowercase the string and put underscores before former uppercase letters. First letter is never prepended with underscore.
Example: "FinalProductPrice" => "final_product_price". */
static inline void vo_underscore(char *str, uint str_len, char **res, uint *res_len TSRMLS_DC)
{
	smart_str result = {0};
	size_t newlen; /* For smart_str_alloc() */
	uint i;
	char current;
	char *walk_start;
	zend_bool symbol_before;

	smart_str_alloc(&result, str_len + 10, 0); /* Expect, that there will be no more than 10 underscores added */

	/* Go through symbols, lowercase and underscore them */
	walk_start = NULL;
	symbol_before = FALSE;
	for (i = 0; i < str_len; i++) {
		current = str[i];
		if ((current >= 'A') && (current <= 'Z')) {
			/* Add processed symbols, if any */
			if (walk_start) {
				smart_str_appendl(&result, walk_start, str + i - walk_start);
				walk_start = NULL;
			}
			/* Add underscore, if there were symbols before - can be non-inuitive, but that is logic of Varien_Object in PHP */
			if (symbol_before) {
				smart_str_appendc(&result, '_');
				symbol_before = FALSE;
			} else {
				symbol_before = TRUE;
			}
			/* Add lowercased symbol */
			current += 'a' - 'A';
			smart_str_appendc(&result, current);
		} else {
			if (!walk_start) {
				walk_start = str + i;
			}
			symbol_before = TRUE;
		}
	}
	if (walk_start) {
		smart_str_appendl(&result, walk_start, str + str_len - walk_start);
	}

	smart_str_0(&result);

	*res_len = result.len;
	*res = result.c;
}

/* public function __call($method, $args) */
PHP_METHOD(Varien_Object, __call)
{
	/* ---PHP---
	switch (substr($method, 0, 3)) {
		case 'get' :
			//Varien_Profiler::start('GETTER: '.get_class($this).'::'.$method);
			$key = $this->_underscore(substr($method,3));
			$data = $this->getData($key, isset($args[0]) ? $args[0] : null);
			//Varien_Profiler::stop('GETTER: '.get_class($this).'::'.$method);
			return $data;

		case 'set' :
			//Varien_Profiler::start('SETTER: '.get_class($this).'::'.$method);
			$key = $this->_underscore(substr($method,3));
			$result = $this->setData($key, isset($args[0]) ? $args[0] : null);
			//Varien_Profiler::stop('SETTER: '.get_class($this).'::'.$method);
			return $result;

		case 'uns' :
			//Varien_Profiler::start('UNS: '.get_class($this).'::'.$method);
			$key = $this->_underscore(substr($method,3));
			$result = $this->unsetData($key);
			//Varien_Profiler::stop('UNS: '.get_class($this).'::'.$method);
			return $result;

		case 'has' :
			//Varien_Profiler::start('HAS: '.get_class($this).'::'.$method);
			$key = $this->_underscore(substr($method,3));
			//Varien_Profiler::stop('HAS: '.get_class($this).'::'.$method);
			return isset($this->_data[$key]);
	}
	throw new Varien_Exception("Invalid method ".get_class($this)."::".$method."(".print_r($args,1).")");
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zend_class_entry **vex;
	int num_args = ZEND_NUM_ARGS();
	char *method, *key;
	uint method_len, key_len;
	zval *key_zval = NULL;
	HashTable *ht_args;
	zval **arg0;
	zval *retval;
	zval **retval_p;
	zval **tmp_pzval;
	zval **data_pzval;

	if (zend_parse_parameters(num_args TSRMLS_CC, "sH", &method, &method_len, &ht_args) == FAILURE) {
		return;
	}
	
	/* Perform appropriate action */
	if (method_len >= 3) {
		if ((method[0] == 'g') && (method[1] == 'e') && (method[2] == 't')) {
			/* ---get--- */
			if (return_value_used) {
				vo_underscore(method + 3, method_len - 3, &key, &key_len TSRMLS_CC);
				ALLOC_INIT_ZVAL(key_zval);
				ZVAL_STRINGL(key_zval, key, key_len, 1);
				if (zend_hash_index_find(ht_args, 0, (void **) &arg0) == SUCCESS) {
					zend_call_method_with_2_params(&obj_zval, obj_ce, NULL, "getdata", &retval, key_zval, *arg0);
				} else {
					zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "getdata", &retval, key_zval);
				}
				zval_ptr_dtor(&key_zval);
				efree(key);

				if (retval) {
					COPY_PZVAL_TO_ZVAL(*return_value, retval);
				} else {
					RETVAL_FALSE;
				}
			}
			return;
		} else if ((method[0] == 's') && (method[1] == 'e') && (method[2] == 't')) {
			/* ---set--- */
			retval_p = return_value_used ? &retval : NULL;
			vo_underscore(method + 3, method_len - 3, &key, &key_len TSRMLS_CC);
			ALLOC_INIT_ZVAL(key_zval);
			ZVAL_STRINGL(key_zval, key, key_len, 1);
			if (zend_hash_index_find(ht_args, 0, (void **) &arg0) == SUCCESS) {
				zend_call_method_with_2_params(&obj_zval, obj_ce, NULL, "setdata", retval_p, key_zval, *arg0);
			} else {
				zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "setdata", retval_p, key_zval);
			}
			zval_ptr_dtor(&key_zval);
			efree(key);

			if (retval_p) {
				if (*retval_p) {
					COPY_PZVAL_TO_ZVAL(*return_value, *retval_p);
				} else {
					RETVAL_FALSE;
				}
			}
			return;
		} else if ((method[0] == 'u') && (method[1] == 'n') && (method[2] == 's')) {
			/* ---uns--- */
			retval_p = return_value_used ? &retval : NULL;
			vo_underscore(method + 3, method_len - 3, &key, &key_len TSRMLS_CC);
			ALLOC_INIT_ZVAL(key_zval);
			ZVAL_STRINGL(key_zval, key, key_len, 1);
			zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "unsetdata", &retval, key_zval);
			zval_ptr_dtor(&key_zval);
			efree(key);

			if (retval_p) {
				if (*retval_p) {
					COPY_PZVAL_TO_ZVAL(*return_value, *retval_p);
				} else {
					RETVAL_FALSE;
				}
			}
			return;
		} else if ((method[0] == 'h') && (method[1] == 'a') && (method[2] == 's')) {
			/* ---has--- */
			if (return_value_used) {
				vo_underscore(method + 3, method_len - 3, &key, &key_len TSRMLS_CC);
				VO_EXTRACT_PROPERTY(_data, obj_zval, &data_pzval);
				if (Z_TYPE_PP(data_pzval) != IS_ARRAY) {
					php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
				}

				if (zend_symtable_find(Z_ARRVAL_PP(data_pzval), key, key_len + 1, (void **) &tmp_pzval) == SUCCESS) {
					RETVAL_BOOL(Z_TYPE_PP(tmp_pzval) != IS_NULL);
				} else {
					RETVAL_FALSE;
				}
			}
			return;
		}
	}

	/* Unknown method called - throw exception */
	if (zend_lookup_class("Varien_Exception", sizeof("Varien_Exception") - 1, &vex TSRMLS_CC) == FAILURE) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "Class Varien_Exception is required to throw an exception in Varien_Object::__call()");
	}
	zend_throw_exception_ex(*vex, 0 TSRMLS_CC, "Invalid method %s::%s", obj_ce->name, method);
}

/* public function __get($var) */
PHP_METHOD(Varien_Object, __get)
{
	/* ---PHP---
	$var = $this->_underscore($var);
	return $this->getData($var);
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	char *var, *var_u;
	uint var_len, var_u_len;
	zval *var_zval;
	zval *retval;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "s!", &var, &var_len) == FAILURE) {
		return;
	}

	ALLOC_INIT_ZVAL(var_zval);
	if (!var) {
		ZVAL_STRINGL(var_zval, "", 0, TRUE);
	} else {
		vo_underscore(var, var_len, &var_u, &var_u_len TSRMLS_CC);
		ZVAL_STRINGL(var_zval, var_u, var_u_len, 1);
		efree(var_u);
	}

	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "getdata", &retval, var_zval);
	zval_ptr_dtor(&var_zval);

	if (retval) {
		COPY_PZVAL_TO_ZVAL(*return_value, retval);
	} else {
		RETVAL_FALSE;
	}
}

/* public function __set($var) */
PHP_METHOD(Varien_Object, __set)
{
	/* ---PHP---
	$var = $this->_underscore($var);
	$this->setData($var, $value);
	*/

	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	char *var, *var_u;
	uint var_len, var_u_len;
	zval *var_zval, *value_zval;

	if (zend_parse_parameters(num_args TSRMLS_CC, "s!z", &var, &var_len, &value_zval) == FAILURE) {
		return;
	}

	ALLOC_INIT_ZVAL(var_zval);
	if (!var) {
		ZVAL_NULL(var_zval);
	} else {
		vo_underscore(var, var_len, &var_u, &var_u_len TSRMLS_CC);
		ZVAL_STRINGL(var_zval, var_u, var_u_len, 1);
		efree(var_u);
	}

	zend_call_method_with_2_params(&obj_zval, obj_ce, NULL, "setdata", NULL, var_zval, value_zval);
	zval_ptr_dtor(&var_zval);
}

/* public function isEmpty() */
PHP_METHOD(Varien_Object, isEmpty)
{
	/* ---PHP---
	if (empty($this->_data)) {
		return true;
	}
	return false;
	*/

	zval *obj_zval = getThis();
	zval **data;

	if (!return_value_used) {
		return;
	}

	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	RETURN_BOOL(!i_zend_is_true(*data));
}

/* protected function _underscore($name) */
PHP_METHOD(Varien_Object, _underscore)
{
	/* ---PHP---
	if (isset(self::$_underscoreCache[$name])) {
		return self::$_underscoreCache[$name];
	}
	#Varien_Profiler::start('underscore');
	$result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
	#Varien_Profiler::stop('underscore');
	self::$_underscoreCache[$name] = $result;
	return $result;
	*/

	int num_args = ZEND_NUM_ARGS();
	zval *name_zval;
	char *name, *name_u;
	uint name_len, name_u_len;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &name_zval) == FAILURE) {
		return;
	}
	VO_VERIFY_SCALAR_INCOMING_VALUE_P(name_zval, "name", return);
	convert_to_string(name_zval);
	name = Z_STRVAL_P(name_zval);
	name_len = Z_STRLEN_P(name_zval);

	vo_underscore(name, name_len, &name_u, &name_u_len TSRMLS_CC);
	RETURN_STRINGL(name_u, name_u_len, FALSE);
}

/* protected function _camelize($name) */
PHP_METHOD(Varien_Object, _camelize)
{
	/* ---PHP---
	return uc_words($name, '');
	*/

	int num_args = ZEND_NUM_ARGS();
	char *name, *name_c;
	uint name_len, name_c_len;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "s", &name, &name_len) == FAILURE) {
		return;
	}

	vo_camelize(name, name_len, NULL, 0, &name_c, &name_c_len TSRMLS_CC);
	RETURN_STRINGL(name_c, name_c_len, FALSE);
}

/* public function serialize($attributes = array(), $valueSeparator='=', $fieldSeparator=' ', $quote='"') */
PHP_METHOD(Varien_Object, serialize)
{
	/* ---PHP---
	$res  = '';
	$data = array();
	if (empty($attributes)) {
		$attributes = array_keys($this->_data);
	}

	foreach ($this->_data as $key => $value) {
		if (in_array($key, $attributes)) {
			$data[] = $key . $valueSeparator . $quote . $value . $quote;
		}
	}
	$res = implode($fieldSeparator, $data);
	return $res;
	*/

	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval *attributes = NULL;
	char *valueSeparator = NULL, *fieldSeparator = NULL, *quote = NULL;
	uint valueSeparator_len, fieldSeparator_len, quote_len;
	zval **data;
	HashTable *ht_data;
	int num_data_elements;
	HashTable *ht_attributes, *ht_serialize;
	int num_attributes, num_serialize;
	smart_str result = {0};
	size_t newlen; /* For smart_str_alloc() */
	zval **attribute;
	long attr_long;
	char *attr_str;
	uint attr_str_len;
	zend_bool is_dispose_attr_str;
	int dummy = 1;
	int current_key_type;
	ulong current_index;
	char *current_key;
	uint current_key_len;
	zval **value;
	zval *tmp_zval;

	if (!return_value_used) {
		return;
	}

	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}
	ht_data = Z_ARRVAL_PP(data);

	num_data_elements = zend_hash_num_elements(ht_data);
	if (!num_data_elements) {
		RETURN_EMPTY_STRING();
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "|z!sss", &attributes, &valueSeparator, 
		&valueSeparator_len, &fieldSeparator, &fieldSeparator_len, &quote, &quote_len) == FAILURE) {
		return;
	}

	/* Check, whether we need to serialize whole data, or just selected attributes */
	if (!attributes || !i_zend_is_true(attributes)) {
		ht_serialize = NULL;
		num_serialize = num_data_elements;
	} else if (Z_TYPE_P(attributes) == IS_ARRAY) {
		ht_attributes = Z_ARRVAL_P(attributes);
		num_attributes = zend_hash_num_elements(ht_attributes);
		/* Prepare hash table, which will be used to check, whether a data value should be serialized, or not */
		ALLOC_HASHTABLE(ht_serialize);
		if (zend_hash_init(ht_serialize, num_attributes, NULL, NULL, FALSE) == FAILURE) {
			FREE_HASHTABLE(ht_serialize);
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for serializing attributes in Varien_Object::serialize()");
		}
		
		for (zend_hash_internal_pointer_reset(ht_attributes); zend_hash_has_more_elements(ht_attributes) == SUCCESS; zend_hash_move_forward(ht_attributes)) {
			zend_hash_get_current_data(ht_attributes, (void **) &attribute);
			expand_to_long_or_string_array_key(*attribute, &attr_long, &attr_str, &attr_str_len, &is_dispose_attr_str TSRMLS_CC);
			if (attr_str) {
				zend_symtable_update(ht_serialize, attr_str, attr_str_len + 1, &dummy, sizeof(int), NULL);
			} else {
				zend_hash_index_update(ht_serialize, attr_long, &dummy, sizeof(int), NULL);
			}
			if (is_dispose_attr_str) {
				efree(attr_str);
			}
		}
		num_serialize = num_attributes;
	} else {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "$attributes, passed to Varien_Object::serialize(), must be empty or of array type");
	}

	/* Prepare data for the iterations */
	smart_str_alloc(&result, 40 * num_serialize, 0); /* Expect, that each value would require 40 bytes */
	if (!valueSeparator) {
		valueSeparator = "=";
		valueSeparator_len = 1;
	}
	if (!fieldSeparator) {
		fieldSeparator = " ";
		fieldSeparator_len = 1;
	}
	if (!quote) {
		quote = "\"";
		quote_len = 1;
	}

	/* Iterate over _data and serialize all the matching values */
	for (zend_hash_internal_pointer_reset(ht_data); zend_hash_has_more_elements(ht_data) == SUCCESS; zend_hash_move_forward(ht_data)) {
		current_key_type = zend_hash_get_current_key_ex(ht_data, &current_key, &current_key_len, &current_index, FALSE, NULL);

		/* Check, that we need to process current key */
		if (ht_serialize) {
			if (current_key_type == HASH_KEY_IS_LONG) {
				if (!zend_hash_index_exists(ht_serialize, current_index)) {
					continue;
				}
			} else {
				if (!zend_hash_exists(ht_serialize, current_key, current_key_len)) {
					continue;
				}
			}
		}

		/* Extract the value to serialize */
		zend_hash_get_current_data(ht_data, (void **) &value);

		/* Add everything to result */
		if (result.len) {
			smart_str_appendl(&result, fieldSeparator, fieldSeparator_len);
		}

		if (current_key_type == HASH_KEY_IS_LONG) {
			smart_str_append_long(&result, (long) current_index);
		} else {
			smart_str_appendl(&result, current_key, current_key_len - 1); /* Hash key len includes final NULL byte */
		}

		smart_str_appendl(&result, valueSeparator, valueSeparator_len);

		smart_str_appendl(&result, quote, quote_len);

		if (Z_TYPE_PP(value) == IS_STRING) {
			if (Z_STRLEN_PP(value)) {
				smart_str_appendl(&result, Z_STRVAL_PP(value), Z_STRLEN_PP(value));
			}
		} else if (Z_TYPE_PP(value) == IS_NULL) {
			/* Do nothing */
		} else if ((Z_TYPE_PP(value) == IS_BOOL) && !Z_BVAL_PP(value)) {
			/* Do nothing */
		} else {
			ALLOC_INIT_ZVAL(tmp_zval);
			MAKE_COPY_ZVAL(value, tmp_zval);
			convert_to_string(tmp_zval);
			if (Z_STRLEN_P(tmp_zval)) {
				smart_str_appendl(&result, Z_STRVAL_P(tmp_zval), Z_STRLEN_P(tmp_zval));
			}
			zval_ptr_dtor(&tmp_zval);
		}

		smart_str_appendl(&result, quote, quote_len);
	}

	/* Free memory */
	if (ht_serialize) {
		FREE_HASHTABLE(ht_serialize);
	}

	/* Result */
	smart_str_0(&result);
	RETURN_STRINGL(result.c, result.len, 0);
}

/* public function getOrigData($key=null) */
PHP_METHOD(Varien_Object, getOrigData)
{
	/* ---PHP---
	if (is_null($key)) {
		return $this->_origData;
	}
	return isset($this->_origData[$key]) ? $this->_origData[$key] : null;
	*/

	int num_args = ZEND_NUM_ARGS();
	zval *obj_zval = getThis();
	zval **origData;
	zval *key;
	long key_long;
	char *key_str;
	uint key_str_len;
	zend_bool is_dispose_key_str;
	int find_result;
	zval **result;

	if (!return_value_used) {
		return;
	}

	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "|z!", &key) == FAILURE) {
			return;
		}
	} else {
		key = NULL;
	}

	VO_EXTRACT_PROPERTY(_origData, obj_zval, &origData);
	if (key == NULL) {
		MAKE_COPY_ZVAL(origData, return_value);
		return;
	} 

	if (Z_TYPE_PP(origData) == IS_NULL) {
		RETURN_NULL();
	} else if (Z_TYPE_PP(origData) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_origData is not an array, while processing it at getOrigData() method");
	}

	expand_to_long_or_string_array_key(key, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);
	if (key_str) {
		find_result = zend_symtable_find(Z_ARRVAL_PP(origData), key_str, key_str_len + 1, (void **) &result);
	} else {
		find_result = zend_hash_index_find(Z_ARRVAL_PP(origData), key_long, (void **) &result);
	}

	if (is_dispose_key_str) {
		efree(key_str);
	}

	if (find_result == FAILURE) {
		RETURN_NULL();
	} else {
		MAKE_COPY_ZVAL(result, return_value);
	}
}

/* public function setOrigData($key=null, $data=null) */
PHP_METHOD(Varien_Object, setOrigData)
{
	/* ---PHP---
    if (is_null($key)) {
        $this->_origData = $this->_data;
    } else {
        $this->_origData[$key] = $data;
    }
    return $this;
	*/

	int num_args = ZEND_NUM_ARGS();
	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval **origData;
	zval **currentData;
	zval *key, *data_param;
	long key_long;
	char *key_str;
	uint key_str_len;
	zend_bool is_dispose_key_str;

	/* Extract parameters */
	if (num_args >= 2) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "|z!z!", &key, &data_param) == FAILURE) {
			return;
		}
	} else if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "|z!", &key) == FAILURE) {
			return;
		}
		data_param = NULL;
	} else {
		key = NULL;
		data_param = NULL;
	}

	/* Update origData */
	if (key == NULL) {
		VO_EXTRACT_PROPERTY(_data, obj_zval, &currentData);
		zend_update_property(obj_ce, obj_zval, "_origData", sizeof("_origData") - 1, *currentData TSRMLS_CC);
	} else {
		if (data_param == NULL) {
			ALLOC_INIT_ZVAL(data_param);
			ZVAL_NULL(data_param);
		} else {
			Z_ADDREF_P(data_param);
		}

		VO_EXTRACT_PROPERTY(_origData, obj_zval, &origData);
		SEPARATE_ZVAL_IF_NOT_REF(origData);
		if (Z_TYPE_PP(origData) != IS_ARRAY) {
			convert_to_array(*origData);
		}

		expand_to_long_or_string_array_key(key, &key_long, &key_str, &key_str_len, &is_dispose_key_str TSRMLS_CC);
		if (key_str) {
			zend_symtable_update(Z_ARRVAL_PP(origData), key_str, key_str_len + 1, &data_param, sizeof(zval *), NULL);
		} else {
			zend_hash_index_update(Z_ARRVAL_PP(origData), key_long, &data_param, sizeof(zval *), NULL);
		}

		if (is_dispose_key_str) {
			efree(key_str);
		}
	}

	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function dataHasChangedFor($field) */
PHP_METHOD(Varien_Object, dataHasChangedFor)
{
	/* ---PHP---
	$newData = $this->getData($field);
	$origData = $this->getOrigData($field);
	return $newData!=$origData;
	*/

	int num_args = ZEND_NUM_ARGS();
	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval *field, *newData, *origData;
	int compare_result;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &field) == FAILURE) {
		return;
	}

	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "getdata", &newData, field);
	if (!newData) {
		RETURN_FALSE;
	}

	zend_call_method_with_1_params(&obj_zval, obj_ce, NULL, "getorigdata", &origData, field);
	if (!origData) {
		zval_ptr_dtor(&newData);
		RETURN_FALSE;
	}

	compare_result = is_not_equal_function(return_value, newData, origData TSRMLS_CC);
	if (compare_result == FAILURE) {
		RETVAL_FALSE;
	}

	zval_ptr_dtor(&newData);
	zval_ptr_dtor(&origData);
}

/* public function setDataChanges($value) */
PHP_METHOD(Varien_Object, setDataChanges)
{
	/* ---PHP---
	$this->_hasDataChanges = (bool)$value;
	return $this;
	*/

	int num_args = ZEND_NUM_ARGS();
	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	zval *value;

	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &value) == FAILURE) {
		return;
	}

	zend_update_property_bool(obj_ce, obj_zval, "_hasDataChanges", sizeof("_hasDataChanges") - 1, zval_is_true(value) TSRMLS_CC);
	
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}

/* public function debug($data=null, &$objects=array()) */
PHP_METHOD(Varien_Object, debug)
{
	/* ---PHP---
	if (is_null($data)) {
		$hash = spl_object_hash($this);
		if (!empty($objects[$hash])) {
			return '*** RECURSION ***';
		}
		$objects[$hash] = true;
		$data = $this->getData();
	}
	$debug = array();
	foreach ($data as $key=>$value) {
		if (is_scalar($value)) {
			$debug[$key] = $value;
		} elseif (is_array($value)) {
			$debug[$key] = $this->debug($value, $objects);
		} elseif ($value instanceof Varien_Object) {
			$debug[$key.' ('.get_class($value).')'] = $value->debug(null, $objects);
		}
	}
	return $debug;
	*/

	/* Not implemented to not waste time on a method, not needed for the concept */
}

/* public function offsetSet($offset, $value) */
PHP_METHOD(Varien_Object, offsetSet)
{
	/* ---PHP---
	$this->_data[$offset] = $value;
	*/
	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval **data;
	zval *offset, *value;
	long offset_long;
	char *offset_str;
	uint offset_str_len;
	zend_bool is_dispose_offset_str;

	if (zend_parse_parameters(num_args TSRMLS_CC, "zz", &offset, &value) == FAILURE) {
		return;
	}

	/* Extract and check _data property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}

	/* Set value to offset */
	Z_ADDREF_P(value);
	expand_to_long_or_string_array_key(offset, &offset_long, &offset_str, &offset_str_len, &is_dispose_offset_str TSRMLS_CC);
	if (offset_str) {
		zend_symtable_update(Z_ARRVAL_PP(data), offset_str, offset_str_len + 1, &value, sizeof(zval *), NULL);
	} else {
		zend_hash_index_update(Z_ARRVAL_PP(data), offset_long, &value, sizeof(zval *), NULL);
	}

	if (is_dispose_offset_str) {
		efree(offset_str);
	}
}

/* public function offsetExists($offset) */
PHP_METHOD(Varien_Object, offsetExists)
{
	/* ---PHP---
	return isset($this->_data[$offset]);
	*/
	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval **data;
	zval *offset;
	long offset_long;
	char *offset_str;
	uint offset_str_len;
	zend_bool is_dispose_offset_str;
	zval **value_pp;
	int find_result;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &offset) == FAILURE) {
		return;
	}

	/* Extract and check _data property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}

	/* Set value to offset */
	expand_to_long_or_string_array_key(offset, &offset_long, &offset_str, &offset_str_len, &is_dispose_offset_str TSRMLS_CC);
	if (offset_str) {
		find_result = zend_symtable_find(Z_ARRVAL_PP(data), offset_str, offset_str_len + 1, (void **) &value_pp);
	} else {
		find_result = zend_hash_index_find(Z_ARRVAL_PP(data), offset_long, (void **) &value_pp);
	}

	if (find_result == SUCCESS) {
		RETVAL_BOOL(Z_TYPE_PP(value_pp) != IS_NULL);
	} else {
		RETVAL_FALSE;
	}

	if (is_dispose_offset_str) {
		efree(offset_str);
	}
}

/* public function offsetUnset($offset) */
PHP_METHOD(Varien_Object, offsetUnset)
{
	/* ---PHP---
	unset($this->_data[$offset]);
	*/
	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval **data;
	zval *offset;
	long offset_long;
	char *offset_str;
	uint offset_str_len;
	zend_bool is_dispose_offset_str;

	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &offset) == FAILURE) {
		return;
	}

	/* Extract and check _data property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}

	/* Set value to offset */
	expand_to_long_or_string_array_key(offset, &offset_long, &offset_str, &offset_str_len, &is_dispose_offset_str TSRMLS_CC);
	if (offset_str) {
		zend_symtable_del(Z_ARRVAL_PP(data), offset_str, offset_str_len + 1);
	} else {
		zend_hash_index_del(Z_ARRVAL_PP(data), offset_long);
	}

	if (is_dispose_offset_str) {
		efree(offset_str);
	}
}

/* public function offsetGet($offset) */
PHP_METHOD(Varien_Object, offsetGet)
{
	/* ---PHP---
	return isset($this->_data[$offset]);
	*/
	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval **data;
	zval *offset;
	long offset_long;
	char *offset_str;
	uint offset_str_len;
	zend_bool is_dispose_offset_str;
	zval **value_pp;
	int find_result;

	if (!return_value_used) {
		return;
	}

	if (zend_parse_parameters(num_args TSRMLS_CC, "z", &offset) == FAILURE) {
		return;
	}

	/* Extract and check _data property */
	VO_EXTRACT_PROPERTY(_data, obj_zval, &data);
	if (Z_TYPE_PP(data) != IS_ARRAY) {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
	}

	/* Set value to offset */
	expand_to_long_or_string_array_key(offset, &offset_long, &offset_str, &offset_str_len, &is_dispose_offset_str TSRMLS_CC);
	if (offset_str) {
		find_result = zend_symtable_find(Z_ARRVAL_PP(data), offset_str, offset_str_len + 1, (void **) &value_pp);
	} else {
		find_result = zend_hash_index_find(Z_ARRVAL_PP(data), offset_long, (void **) &value_pp);
	}

	if (find_result == SUCCESS) {
		MAKE_COPY_ZVAL(value_pp, return_value);
	} else {
		RETVAL_NULL();
	}

	if (is_dispose_offset_str) {
		efree(offset_str);
	}
}

/* public function isDirty($field=null) */
PHP_METHOD(Varien_Object, isDirty)
{
	/* ---PHP---
	if (empty($this->_dirty)) {
		return false;
	}
	if (is_null($field)) {
		return true;
	}
	return isset($this->_dirty[$field]);
	*/
	zval *obj_zval = getThis();
	int num_args = ZEND_NUM_ARGS();
	zval **dirty_pp;
	HashTable *htDirty;
	zval *field;
	long field_long;
	char *field_str;
	uint field_str_len;
	zend_bool is_dispose_field_str;
	zval **value_pp;
	int find_result;

	if (!return_value_used) {
		return;
	}

	VO_EXTRACT_PROPERTY(_dirty, obj_zval, &dirty_pp);
	if (Z_TYPE_PP(dirty_pp) == IS_NULL) {
		ALLOC_HASHTABLE(htDirty);
		if (zend_hash_init(htDirty, 16, NULL, ZVAL_PTR_DTOR, TRUE) == FAILURE) {
			FREE_HASHTABLE(htDirty);
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for object _dirty property");
		}

		Z_ARRVAL_PP(dirty_pp) = htDirty;
		Z_TYPE_PP(dirty_pp) = IS_ARRAY;
	} else if (Z_TYPE_PP(dirty_pp) == IS_ARRAY) {
		htDirty = Z_ARRVAL_PP(dirty_pp);
	} else {
		php_error_docref(NULL TSRMLS_CC, E_ERROR, "_dirty property must be array");
	}

	if (!zend_hash_num_elements(htDirty)) {
		RETURN_FALSE;
	}

	if (num_args) {
		if (zend_parse_parameters(num_args TSRMLS_CC, "z!", &field) == FAILURE) {
			return;
		}
		VO_VERIFY_SCALAR_INCOMING_VALUE_P(field, "field", return);
	} else {
		field = NULL;
	}

	if (!field) {
		RETURN_TRUE;
	}

	expand_to_long_or_string_array_key(field, &field_long, &field_str, &field_str_len, &is_dispose_field_str TSRMLS_CC);
	if (field_str) {
		find_result = zend_symtable_find(htDirty, field_str, field_str_len + 1, (void **) &value_pp);
	} else {
		find_result = zend_hash_index_find(htDirty, field_long, (void **) &value_pp);
	}
	if (is_dispose_field_str) {
		efree(field_str);
	}

	if ((find_result == SUCCESS) && (Z_TYPE_PP(value_pp) != IS_NULL)) {
		RETURN_TRUE;
	} else {
		RETURN_FALSE;
	}
}

/* public function flagDirty($field, $flag=true) */
PHP_METHOD(Varien_Object, flagDirty)
{
	/* ---PHP---
	if (is_null($field)) {
		foreach ($this->getData() as $field=>$value) {
			$this->flagDirty($field, $flag);
		}
	} else {
		if ($flag) {
			$this->_dirty[$field] = true;
		} else {
			unset($this->_dirty[$field]);
		}
	}
	return $this;
	*/
	zval *obj_zval = getThis();
	zend_class_entry *obj_ce = Z_OBJCE_P(obj_zval);
	int num_args = ZEND_NUM_ARGS();
	zval *field, *flag = NULL;
	zval *flag_local = NULL;
	zval *data = NULL;
	HashTable *htData;
	int now_field_type;
	char *now_field_str;
	uint now_field_len;
	ulong now_field_index;
	zval *now_field_zval;
	zval **dirty_pp;
	HashTable *htDirty;
	long field_long;
	char *field_str;
	uint field_str_len;
	zend_bool is_dispose_field_str;
	zval *value_true;

	if (zend_parse_parameters(num_args TSRMLS_CC, "z!|z", &field, &flag) == FAILURE) {
		return;
	}
	VO_VERIFY_SCALAR_INCOMING_VALUE_P(field, "field", return);

	if (!flag) {
		ALLOC_INIT_ZVAL(flag_local);
		ZVAL_TRUE(flag_local);
		flag = flag_local;
	}
	#define FREE_RESOURCES() {if (flag_local) {zval_ptr_dtor(&flag_local); flag_local = NULL;}}

	if (!field) {
		zend_call_method_with_0_params(&obj_zval, obj_ce, NULL, "getdata", &data);
		if (!data) {
			FREE_RESOURCES();
			RETURN_FALSE;
		}
		if (Z_TYPE_P(data) != IS_ARRAY) {
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "_data property must be array");
		}
		htData = Z_ARRVAL_P(data);

		ALLOC_INIT_ZVAL(now_field_zval);
		for (zend_hash_internal_pointer_reset(htData); zend_hash_has_more_elements(htData) == SUCCESS; zend_hash_move_forward(htData)) {
			now_field_type = zend_hash_get_current_key_ex(htData, &now_field_str, &now_field_len, &now_field_index, FALSE, NULL);
			if (now_field_type == HASH_KEY_IS_LONG) {
				ZVAL_LONG(now_field_zval, now_field_index); 
			} else {
				ZVAL_STRINGL(now_field_zval, now_field_str, now_field_len - 1, TRUE); 
			}

			zend_call_method_with_2_params(&obj_zval, obj_ce, NULL, "flagdirty", NULL, now_field_zval, flag);
		}
		zval_ptr_dtor(&now_field_zval);

	} else {
		VO_EXTRACT_PROPERTY(_dirty, obj_zval, &dirty_pp);

		if (Z_TYPE_PP(dirty_pp) == IS_NULL) {
			ALLOC_HASHTABLE(htDirty);
			if (zend_hash_init(htDirty, 16, NULL, ZVAL_PTR_DTOR, TRUE) == FAILURE) {
				FREE_HASHTABLE(htDirty);
				php_error_docref(NULL TSRMLS_CC, E_ERROR, "Unable to init HashTable for object _dirty property");
			}

			Z_ARRVAL_PP(dirty_pp) = htDirty;
			Z_TYPE_PP(dirty_pp) = IS_ARRAY;
		} else if (Z_TYPE_PP(dirty_pp) == IS_ARRAY) {
			htDirty = Z_ARRVAL_PP(dirty_pp);
		} else {
			php_error_docref(NULL TSRMLS_CC, E_ERROR, "_dirty property must be array");
		}

		expand_to_long_or_string_array_key(field, &field_long, &field_str, &field_str_len, &is_dispose_field_str TSRMLS_CC);
		if (i_zend_is_true(flag)) {

			ALLOC_INIT_ZVAL(value_true);
			ZVAL_TRUE(value_true);

			if (field_str) {
				zend_symtable_update(htDirty, field_str, field_str_len + 1, &value_true, sizeof(zval *), NULL);
			} else {
				zend_hash_index_update(htDirty, field_long, &value_true, sizeof(zval *), NULL);
			}

		} else {
			if (field_str) {
				zend_symtable_del(htDirty, field_str, field_str_len + 1);
			} else {
				zend_hash_index_del(htDirty, field_long);
			}
		}

		if (is_dispose_field_str) {
			efree(field_str);
		}
	}

	FREE_RESOURCES();

	/*
	--PHP---
	return $this;
	*/
	if (return_value_used) {
		MAKE_COPY_ZVAL(&obj_zval, return_value);
	}
}