<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
            return new class($pdo, $database, $prefix, $config) extends SQLiteConnection
            {
                protected function executeBeginTransactionStatement()
                {
                    try {
                        parent::executeBeginTransactionStatement();
                    } catch (\PDOException $e) {
                        if (str_contains($e->getMessage(), 'already an active transaction') || str_contains($e->getMessage(), 'cannot start a transaction within a transaction')) {
                            return;
                        }
                        throw $e;
                    }
                }

                public function select($query, $bindings = [], $useReadPdo = true)
                {
                    if (str_contains(strtolower($query), 'set sql_mode')) {
                        return [];
                    }

                    return parent::select($query, $bindings, $useReadPdo);
                }

                public function statement($query, $bindings = [])
                {
                    if (str_contains(strtolower($query), 'set sql_mode')) {
                        return true;
                    }

                    return parent::statement($query, $bindings);
                }

                protected function getDefaultSchemaGrammar()
                {
                    $grammar = new class($this) extends SQLiteGrammar
                    {
                        public function getValue($value)
                        {
                            $valStr = $value instanceof Expression
                                ? (string) $value->getValue($this->connection->getQueryGrammar())
                                : (string) $value;
                            if (str_contains(strtolower($valStr), 'on update current_timestamp')) {
                                return 'NULL';
                            }

                            return parent::getValue($value);
                        }
                    };

                    return $grammar;
                }
            };
        });

        if (! $this->app) {
            $this->refreshApplication();
        }

        $db = $this->app->make('db');
        // dump("Cached connections before extend: ", array_keys($db->getConnections()));
        $db->purge('eudr_ts');
        $db->purge('eudr_ts_pg');
        $db->purge('mysql');

        $sqliteConnection = $db->connection('sqlite');
        $db->extend('eudr_ts', fn () => $sqliteConnection);
        $db->extend('eudr_ts_pg', fn () => $sqliteConnection);
        $db->extend('mysql', fn () => $sqliteConnection);

        parent::setUp();
    }
}
