#include "php.h"
#include "temp.h"

int temp_print_key(zval **zv TSRMLS_DC, int num_args, va_list args, zend_hash_key *hash_key)
{
	if (hash_key->nKeyLength) {
		/* String Key / Associative */
		PHPWRITE(hash_key->arKey, hash_key->nKeyLength);
		php_printf(":%d, ", hash_key->nKeyLength);
	} else {
		php_printf("%ld, ", hash_key->h);
	}
	return ZEND_HASH_APPLY_KEEP;
}

void temp_print_array_keys(HashTable *ht TSRMLS_DC)
{
	zend_hash_apply_with_arguments(ht TSRMLS_CC, temp_print_key, 0);
}