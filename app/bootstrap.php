<?php
/**
 * Bootstrap — Autoloader & global setup
 * Sertakan file ini di setiap halaman PHP:
 *   require_once dirname(__DIR__) . '/app/bootstrap.php';
 */

define('APP_ROOT',  dirname(__DIR__) . '/app');
define('BASE_PATH', dirname(__DIR__));

// Composer autoloader (PhpSpreadsheet, dll.)
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Konfigurasi timezone
$appConfig = require APP_ROOT . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

// Error reporting berdasarkan environment
if ($appConfig['env'] === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Simple autoloader — load kelas dari folder app/core & app/models
spl_autoload_register(function (string $class): void {
    $searchDirs = [
        APP_ROOT . '/core/',
        APP_ROOT . '/models/',
        APP_ROOT . '/helpers/',
    ];
    foreach ($searchDirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Mulai session
Auth::startSession();
