<?php
/**
 * Database Configuration
 * Edit nilai-nilai ini sesuai kredensial MySQL Anda
 */

return [
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'classroom_star',   // ← Ganti dengan nama database Anda
    'username' => 'root',             // ← Ganti dengan username MySQL Anda
    'password' => '',                 // ← Ganti dengan password MySQL Anda
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
