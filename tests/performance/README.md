Performance Comparison
=====================

These tests are used for performance comparison of Magento with original PHP Varien_Object and Magento with C Varien_Object.
The usual Checkout scenario is tested - average response time is measured for rendering a product page, adding product to the cart and performing the checkout.

Requirements
-----------

In order to run the tests, you will need:
- [Apache JMeter](http://jmeter.apache.org/download_jmeter.cgi)
- [Magento CE](http://magento.com) fully installed and working on Apache, MySQL and PHP

Configuring Magento
-----------

Magento needs to be configured with the following data:
- Product needs to be created, so that it is accessible via `product.html` relative path (e.g. http://localhost/product.html)

Configuring Tests
-----------

Copy `config.php.dist` to `config.php` and configure the data according to your system settings.

How to Run
-----------

Enable or disable `php_mage` extension, depending on the mode you wish to test Magento in: with original PHP Varien_Object or with the C implementation.
Ensure, that you've closed all the non-important processes, so that performance results are minimally affected by OS and other applications running.
Go to `tests/performance` and run the `run_tests.php` script:

	cd tests/performance
	php run_tests.php

The script will run Checkout scenario. Time, needed for testing, depends on the machine power and configured settings (i.e. number of users and loops). Eventually, the script will output the average number of seconds that were needed to walk through the Checkout scenario.
