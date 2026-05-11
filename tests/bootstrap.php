<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('_ECRIRE_INC_VERSION')) {
    define('_ECRIRE_INC_VERSION', 'test');
}

// IMPORTANT: All function stubs use if (!function_exists()) so they don't conflict
// with SPIP functions when loaded in integration test context (bootstrap_integration.php)

if (!function_exists('include_spip')) {
    function include_spip(string $path): void {
    }
}

if (!function_exists('_request')) {
    function _request(string $name) {
        return $_REQUEST[$name] ?? null;
    }
}

if (!function_exists('_T')) {
    function _T(string $key): string {
        return $key;
    }
}

if (!function_exists('sql_quote')) {
    function sql_quote(string $value): string {
        return "'" . addslashes($value) . "'";
    }
}

if (!function_exists('sql_countsel')) {
    function sql_countsel(string $table, string $where): int {
        return (int) ($GLOBALS['_test_sql_countsel'][$table] ?? 0);
    }
}

if (!function_exists('lire_config')) {
    function lire_config(string $key) {
        return $GLOBALS['_test_config'][$key] ?? null;
    }
}

if (!function_exists('autoriser')) {
    function autoriser(string $faire, string $type = '', int $id = 0, array $qui = [], array $opt = []): bool {
        return (bool) ($GLOBALS['_test_autoriser'] ?? true);
    }
}

