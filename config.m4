PHP_ARG_ENABLE(mage, for Mage support, [  --enable-mage          Enable Mage support])

if test "$PHP_MAGE" != "no"; then
  AC_DEFINE(HAVE_MAGE, 1, [ ])
  PHP_NEW_EXTENSION(mage, php_mage.c varien_object.c, $ext_shared)
fi

