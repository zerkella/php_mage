<?php
// Assert preconditions
if (!extension_loaded('mage')) {
    throw new Exception('Extension "mage" is not loaded');
}
if (!class_exists('Varien_Object', false)) {
    throw new Exception('Class Varien_Object must be available, and without autoload');
}

// Setup autloader to load tested descendants and framework classes automatically
$autoloader = function ($class) {
    $prefixes = array(
        'Zerkella_PhpMage_Varien_Object_Descendant_' => __DIR__ . '/../testsuite/Varien/Object/_files/Descendant',
        'Zerkella_PhpMage_' => __DIR__,
    );
    foreach ($prefixes as $prefix => $dir) {
        if (strncmp($class, $prefix, strlen($prefix)) != 0) {
            continue;
        }
        $classNameBody = substr($class, strlen($prefix));
        $subPath = str_replace('_', '/', $classNameBody);
        require $dir . '/' . $subPath . '.php';
        break;
    }
};
spl_autoload_register($autoloader);
unset($autoloader);
