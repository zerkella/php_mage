#ifndef MAGE_H
#define MAGE_H

#ifdef WIN32
	/*
	 Must be changes to the actual version of compiler, that compiles this dll.
	 This is used by PHP to check extension compatibility with core.
	*/
	#define PHP_COMPILER_ID  "VC9"
#endif

#include "php.h"

#define PHP_ZERK_MAGE_NAME "mage"
#define PHP_ZERK_MAGE_VERSION "0.01"

extern zend_module_entry mage_module_entry;

#endif