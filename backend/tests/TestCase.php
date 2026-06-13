<?php declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Database\Connection;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
            return new class($pdo, $database, $prefix, $config) extends \Illuminate\Database\SQLiteConnection {
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
                    $grammar = new class($this) extends \Illuminate\Database\Schema\Grammars\SQLiteGrammar {
                        public function getValue($value)
                        {
                            $valStr = $value instanceof \Illuminate\Contracts\Database\Query\Expression
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

        parent::setUp();
    }
}
