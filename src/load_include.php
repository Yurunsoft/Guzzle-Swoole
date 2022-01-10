<?php

if (!function_exists('GuzzleHttp\uri_template') && !function_exists('GuzzleHttp\describe_type') && !class_exists(\GuzzleHttp\Utils::class, false))
{
    $file = __DIR__ . '/load.php';
    if (is_file($file))
    {
        require_once $file;
    }
    unset($file);
}
