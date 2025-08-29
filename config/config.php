<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Registration
    |--------------------------------------------------------------------------
    |
    | When enabled, the autoloader will automatically register itself with
    | PHP's spl_autoload_register during service provider boot.
    |
    */
    'auto_register' => env('COMPOSER_AUTOLOAD_AUTO_REGISTER', false),

    /*
    |--------------------------------------------------------------------------
    | Autoload Configuration
    |--------------------------------------------------------------------------
    |
    | This section defines the autoload configuration similar to composer.json
    | You can define PSR-4 namespaces, classmap, and files to be loaded.
    |
    */
    'autoload' => [
        /*
        |----------------------------------------------------------------------
        | PSR-4 Namespaces
        |----------------------------------------------------------------------
        |
        | Define PSR-4 namespace mappings. Each key should be a namespace with
        | trailing backslash, and the value should be the path or array of paths.
        |
        */
        'psr-4' => [
            // 'App\\Custom\\' => base_path('app/Custom'),
            // 'Vendor\\Package\\' => [
            //     base_path('packages/vendor/package/src'),
            //     base_path('packages/vendor/package/lib'),
            // ],
        ],

        /*
        |----------------------------------------------------------------------
        | Class Map
        |----------------------------------------------------------------------
        |
        | Define specific class to file mappings for classes that don't follow
        | PSR-4 conventions or for optimization purposes.
        |
        */
        'classmap' => [
            // 'Legacy\\ClassName' => base_path('legacy/ClassName.php'),
            // 'Custom\\Helper' => base_path('helpers/CustomHelper.php'),
        ],

        /*
        |----------------------------------------------------------------------
        | Files
        |----------------------------------------------------------------------
        |
        | Define files that should be included when the autoloader is registered.
        | These are typically helper files or bootstrap files.
        |
        */
        'files' => [
            // base_path('helpers/functions.php'),
            // base_path('bootstrap/custom.php'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to development environment
    |
    */
    'development' => [
        'cache_enabled' => env('COMPOSER_AUTOLOAD_CACHE', false),
        'cache_path' => storage_path('framework/cache/composer-autoload'),
        'debug' => env('COMPOSER_AUTOLOAD_DEBUG', false),
    ],
];
