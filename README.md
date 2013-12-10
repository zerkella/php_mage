php_mage - Magento Varien_Object as PHP extension
=====================

This is a proof of a concept - implementation of Varien_Object from Magento 1 done on C as a PHP extension. The reason behind this work is to investigate possible performance gain/loss by manually converting a core Magento class to a pre-compiled extension.
The implementation is very close to original Varien_Object. There are minor differences, made for optimization purpose. However, they won't show up, if the class won't be used in some extremely ~~stupid~~ funny way. 

Supported Magento versions:
* Magento 1 CE from 1.6.0.0 and higher
* Magento 1 EE from 1.11.0.0 and higher

Tested PHP versions:
* 5.3.14
 
Tested platforms:
* Windows 7 64-bit

Used compilation tool:
- VC 2008 Express

How to Build
-----------

See [BUILD.md](docs/BUILD.md)

How to Test
-----------

There are tests for the whole implementation in `/tests` directory. They are written in PHP, using PHPUnit. Running is pretty straightforward:

	cd tests
	phpunit
	
The framework supports running tests against either php_mage or native Varien_Object. See `phpunit.xml.dist` for the option, how to configure that. 
