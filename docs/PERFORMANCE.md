Performance comparison of php_mage
=====================

Here is the investigation on the results achieved by moving Magento Varien_Object implementation from PHP to C, thus making Varien_Object a PHP extension.

The change is **~3% performance improvement**, which depends on use case scenarios, Magento settings and server configuration. The more optimized Magento and server are - the more beneficial the extension is.

Environment configuration used for performance investigation:
* Windows 7 x64
* 4Gb RAM
* 32-bit PHP 5.3.14
* MySQL 5.5.20
* Apache 2.2.21
* APC 3.1.10 for opcode cache
* Magento cache: APC as fast backend, database as slow
* Magento 1.8.1.0

The average time for checkout scenario is 5.15 seconds for original Magento and 5.01 seconds for Magento with php_mage extension.
[HHVM](http://www.hhvm.com/blog/) seems as a better alternative to the approach, because it removes necessity of manual translation of PHP code into C. However, HHVM is not capable of doing intelligent logic optimization.