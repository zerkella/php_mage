php_mage - Magento Varien_Object as PHP extension
=====================

This is a proof of a concept - implementation of Varien_Object from Magento 1 done on C as a PHP extension. The reason behind this work is to investigate possible performance gain/loss by manually converting a core Magento class to a pre-compiled extension.
The implementation is very close to original Varien_Object. There are minor differences, made for optimization purpose. However, they won't show up, if the class won't be used in some extremely ~~stupid~~ funny way. 

Supported Magento versions:
* Magento CE 1.6.0.0 and higher
* Magento EE 1.11.0.0 and higher

Tested PHP versions:
* 5.3.14
* 5.6.5
 
Tested platforms:
* Windows 7 64-bit
* Debian 7.8

Used compilation tool:
- VC 2008 Express
- GCC 4.7.2

Performance Comparison
----------------------

Results of the performance comparison are in [docs/PERFORMANCE.md](docs/PERFORMANCE.md)

How to Build
-----------

See [BUILD.md](docs/BUILD.md)
Already compiled php extension for Windows / PHP 5.3.14 is here: [php_mage.dll](https://dl.dropboxusercontent.com/u/17950262/various/php_mage.dll)

How to Unit Test
-----------

There are unit tests for the whole implementation in `/tests` directory. They are written in PHP, using PHPUnit. Running is pretty straightforward:

	cd tests/unit
	phpunit
	
The framework supports running unit tests against either php_mage or native Varien_Object. See `phpunit.xml.dist` for the option, how to configure that. 

How to Test Performance
-----------

See performance tests [README.md](tests/performance/README.md).

How to Install
-----------

Having the built extension, just put it into `php.ini` as any other extension.

	extension=php_mage.dll

The extension will add `Varien_Object` class to the list of internal PHP classes. Magento won't need to autoload this class, so that it won't load the original `Varien_Object` that is coming with the system.
You can completely delete the `/lib/Varien/Object.php` file from Magento in order to ensure, that it runs using php_mage extension only.

Original author: 
_Andrey Tserkus_
_Software Engineer, Magento / eBay_
_Dec, 2013_

Compatibility for PHP 5.6 on Linux:
_Robert Eisele_
_Software Engineer, www.xarg.org_
_Feb, 2015_
