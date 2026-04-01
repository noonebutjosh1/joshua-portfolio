<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set((string) (getenv("APP_TIMEZONE") ?: "UTC"));

define("APP_NAME", "Vaultwear");
define("APP_URL", rtrim((string) (getenv("APP_URL") ?: "/Vaultwear"), "/"));

define("DB_HOST", (string) (getenv("DB_HOST") ?: "127.0.0.1"));
define("DB_PORT", (string) (getenv("DB_PORT") ?: "3306"));
define("DB_NAME", (string) (getenv("DB_NAME") ?: "vaultwear"));
define("DB_USER", (string) (getenv("DB_USER") ?: "root"));
define("DB_PASS", (string) (getenv("DB_PASS") ?: ""));

error_reporting(E_ALL);
ini_set("display_errors", (string) (getenv("APP_DEBUG") ?: "1"));
