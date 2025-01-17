<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb0b5c6683af8fc94908b95b0804e9e70
{
    public static $files = array (
        '5d9c5be1aa1fbc12016e2c5bd16bbc70' => __DIR__ . '/..' . '/dusank/knapsack/src/collection_functions.php',
        'e5fde315a98ded36f9b25eb160f6c9fc' => __DIR__ . '/..' . '/dusank/knapsack/src/utility_functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WPSteak\\' => 8,
        ),
        'S' => 
        array (
            'ShipSmart\\' => 10,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'Psr\\Container\\' => 14,
        ),
        'L' => 
        array (
            'League\\Container\\' => 17,
        ),
        'D' => 
        array (
            'DusanKasan\\Knapsack\\' => 20,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
            'Cedaro\\WP\\Plugin\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WPSteak\\' => 
        array (
            0 => __DIR__ . '/..' . '/apiki/wpsteak/src',
        ),
        'ShipSmart\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Psr\\Container\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/container/src',
        ),
        'League\\Container\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/container/src',
        ),
        'DusanKasan\\Knapsack\\' => 
        array (
            0 => __DIR__ . '/..' . '/dusank/knapsack/src',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
        'Cedaro\\WP\\Plugin\\' => 
        array (
            0 => __DIR__ . '/..' . '/cedaro/wp-plugin/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb0b5c6683af8fc94908b95b0804e9e70::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb0b5c6683af8fc94908b95b0804e9e70::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb0b5c6683af8fc94908b95b0804e9e70::$classMap;

        }, null, ClassLoader::class);
    }
}
