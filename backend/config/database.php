<?php declare(strict_types=1);

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | The default connection used for all Eloquent models and DB queries
    | that do not specify an explicit $connection. Set to eudr_ts which
    | points to PostgreSQL eudr_dev — the single EUDR application database.
    |
    */

    'default' => env('DB_CONNECTION', 'eudr_ts'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        // ─── SQLite (PHPUnit in-memory tests only) ───────────────────────────
        'sqlite' => [
            'driver'                  => 'sqlite',
            'url'                     => env('DB_URL'),
            'database'                => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'                  => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout'            => null,
            'journal_mode'            => null,
            'synchronous'             => null,
            'transaction_mode'        => 'DEFERRED',
        ],

        // ─── Primary App Database: PostgreSQL eudr_dev ───────────────────────
        // All EUDR application tables. Models with protected $connection = 'eudr_ts'
        // and models without an explicit connection both use this.
        'eudr_ts' => [
            'driver'         => env('DB_TS_CONNECTION', 'pgsql'),
            'host'           => env('DB_TS_HOST', '127.0.0.1'),
            'port'           => env('DB_TS_PORT', '5432'),
            'database'       => env('DB_TS_DATABASE', 'eudr_dev'),
            'username'       => env('DB_TS_USERNAME', 'eudr_app'),
            'password'       => env('DB_TS_PASSWORD', ''),
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => env('DB_TS_SSLMODE', 'disable'),
            'charset'        => env('DB_TS_CHARSET', 'utf8'),
            'options'        => [
                \PDO::ATTR_TIMEOUT         => env('DB_TS_TIMEOUT', 10),
                \PDO::ATTR_PERSISTENT      => filter_var(env('DB_TS_PERSISTENT', false), FILTER_VALIDATE_BOOLEAN),
                \PDO::ATTR_ERRMODE         => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ],
        ],

        // ─── OEE MySQL (External — Packaging/Label integration) ──────────────
        // Queries oee_756.* tables from EloquentShipmentRepository.
        // Specification TBD — connection will fail-silently until configured.
        // Set OEE_DB_* env vars when OEE server specs are available.
        'oee' => [
            'driver'   => 'mysql',
            'host'     => env('OEE_DB_HOST', '127.0.0.1'),
            'port'     => env('OEE_DB_PORT', '3309'),
            'database' => env('OEE_DB_DATABASE', 'oee_756'),
            'username' => env('OEE_DB_USERNAME', 'root'),
            'password' => env('OEE_DB_PASSWORD', ''),
            'charset'  => 'utf8mb4',
            'prefix'   => '',
            'strict'   => true,
            'engine'   => null,
            'options'  => [
                \PDO::ATTR_TIMEOUT => 3,
            ],
        ],

        // ─── DCS Flowmeter / Airflow Historian (External — MySQL) ────────────
        // Used by m-quantifier module for DCS Flowmeter data.
        // Specification TBD — connection will fail-silently until configured.
        // Set DWSQL_* env vars when DCS server specs are available.
        'dwsql' => [
            'driver'   => 'mysql',
            'host'     => env('DWSQL_HOST', '172.16.11.19'),
            'port'     => env('DWSQL_PORT', '3302'),
            'database' => env('DWSQL_DATABASE', 'EOB1_SQL_7AM.EUDR_AIRFLOW'),
            'username' => env('DWSQL_USERNAME', 'root'),
            'password' => env('DWSQL_PASSWORD', ''),
            'charset'  => 'utf8mb4',
            'prefix'   => '',
            'strict'   => true,
            'engine'   => null,
            'options'  => [
                \PDO::ATTR_TIMEOUT => 2,
            ],
        ],

        // ─── PostgreSQL (Laravel default — unused, kept for reference) ────────
        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '5432'),
            'database'       => env('DB_DATABASE', 'laravel'),
            'username'       => env('DB_USERNAME', 'root'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => env('DB_CHARSET', 'utf8'),
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => env('DB_SSLMODE', 'prefer'),
        ],

        // ─── SQL Server (reserved — not currently used) ───────────────────────
        'sqlsrv' => [
            'driver'         => 'sqlsrv',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', 'localhost'),
            'port'           => env('DB_PORT', '1433'),
            'database'       => env('DB_DATABASE', 'laravel'),
            'username'       => env('DB_USERNAME', 'root'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => env('DB_CHARSET', 'utf8'),
            'prefix'         => '',
            'prefix_indexes' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table'                => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster'    => env('REDIS_CLUSTER', 'redis'),
            'prefix'     => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url'               => env('REDIS_URL'),
            'host'              => env('REDIS_HOST', '127.0.0.1'),
            'username'          => env('REDIS_USERNAME'),
            'password'          => env('REDIS_PASSWORD'),
            'port'              => env('REDIS_PORT', '6379'),
            'database'          => env('REDIS_DB', '0'),
            'max_retries'       => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base'      => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap'       => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url'               => env('REDIS_URL'),
            'host'              => env('REDIS_HOST', '127.0.0.1'),
            'username'          => env('REDIS_USERNAME'),
            'password'          => env('REDIS_PASSWORD'),
            'port'              => env('REDIS_PORT', '6379'),
            'database'          => env('REDIS_CACHE_DB', '1'),
            'max_retries'       => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base'      => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap'       => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
