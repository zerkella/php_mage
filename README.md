php_mage - Magento Varien_Object as PHP extension
=====================

This is a proof of a concept - implementation of Varien_Object from Magento 1 done on C as a PHP extension. The reason behind this work is to investigate possible performance gain/loss by manually converting a core Magento class to a pre-compiled extension.
The implementation is very close to original Varien_Object. There are minor differences, made for optimization purpose. However, they won't show up, if the class won't be used in some extremely ~~stupid~~ funny way. 

Supported Magento versions:
* Magento CE 1.6.0.0 and higher
* Magento EE 1.11.0.0 and higher

Tested PHP versions:
* 5.3.14
 
Tested platforms:
* Windows 7 64-bit

Used compilation tool:
- VC 2008 Express

How to Build
-----------

See [BUILD.md](docs/BUILD.md)

How to Unit Test
-----------

There are unit tests for the whole implementation in `/tests` directory. They are written in PHP, using PHPUnit. Running is pretty straightforward:

	cd tests/unit
	phpunit
	
The framework supports running unit tests against either php_mage or native Varien_Object. See `phpunit.xml.dist` for the option, how to configure that. 

How to Install
-----------

Having the built extension, just put it into `php.ini` as any other extension.

	extension=php_mage.dll

The extension will add `Varien_Object` class to the list of internal PHP classes. Magento won't need to autoload this class, so that it won't load the original `Varien_Object` that is coming with the system.
You can completely delete the `/lib/Varien/Object.php` file fron Magento in order to ensure, that it runs using php_mage extension only.
