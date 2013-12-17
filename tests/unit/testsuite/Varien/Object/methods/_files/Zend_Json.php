<?php
/**
 * Just a custom Zend_Json class to test __toJson() method
 */
class Zend_Json
{
    /**
     * @var callable
     */
    protected static $_callback;

    /**
     * Analog of original Zend_Json method
     */
    public static function encode()
    {
        if (!self::$_callback) {
            throw new LogicException('Callback is not set for Zend_Json::encode() method');
        }
        return call_user_func_array(self::$_callback, func_get_args());
    }

    /**
     * @param callable|null $callback
     */
    public static function setCallback($callback)
    {
        self::$_callback = $callback;
    }
}
