<?php

if (!function_exists('GuzzleHttp\uri_template') && !function_exists('GuzzleHttp\describe_type'))
{
    $file = __DIR__ . '/load.php';
    if (is_file($file))
    {
        require_once $file;
    }
    unset($file);
}
