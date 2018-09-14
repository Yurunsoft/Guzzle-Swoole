<?php
namespace GuzzleHttp;

abstract class DefaultHandler
{
    /** @var string|callable|null */
    private static $defaultHandler;
    
    /**
     * Set a default handler
     *
     * @param string|callable|null $handler class name or callable. If value is null, that has no default handler
     * @return void
     */
    public static function setDefaultHandler($handler)
    {
        static::$defaultHandler = $handler;
    }

    /**
     * Get default handler
     * 
     * If return null, that has no default handler
     *
     * @return string|callable|null
     */
    public static function getDefaultHandler()
    {
        return static::$defaultHandler ?: null;
    }
}