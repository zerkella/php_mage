<?php
if (!extension_loaded('mage')) {
	echo 'Extension is not loaded';
	exit(1);
}

error_reporting(E_ALL);

echo 'Class Varien_Object ', (class_exists('Varien_Object') ? 'exists' : 'does not exist'); 

echo "\nFinished";