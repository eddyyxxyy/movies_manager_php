<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'name' => getenv('APP_NAME') ?: 'PHP Custom Framework',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */
    'env' => getenv('APP_ENV') ?: 'production',

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */
    'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Application Directory Paths
    |--------------------------------------------------------------------------
    */
    'views_dir' => getenv('VIEWS_DIR') ?: __DIR__ . '/../src/View/',
    'cache_dir' => getenv('CACHE_DIR') ?: __DIR__ . '/../cache/',
    'upload_dir' => getenv('UPLOAD_DIR') ?: __DIR__ . '/../public/uploads/images',
];