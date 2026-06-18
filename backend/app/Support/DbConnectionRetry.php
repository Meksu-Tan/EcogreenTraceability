<?php declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\QueryException;
use PDOException;
use RuntimeException;

class DbConnectionRetry
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 500;

    public static function execute(callable $callback, int $maxRetries = self::MAX_RETRIES)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $callback();
            } catch (QueryException | PDOException $e) {
                $lastException = $e;

                // Only retry on connection-related errors
                if (!self::isRetryableError($e)) {
                    throw $e;
                }

                if ($attempt < $maxRetries) {
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
                }
            }
        }

        throw new RuntimeException(
            "Database operation failed after {$maxRetries} attempts: {$lastException->getMessage()}",
            previous: $lastException
        );
    }

    private static function isRetryableError(\Throwable $e): bool
    {
        $message = $e->getMessage();
        $code = $e->getCode();

        // PostgreSQL connection errors
        $retryablePatterns = [
            'connection refused',
            'connection timeout',
            'no pg_hba.conf entry',
            'server closed the connection',
            'lost synchronization',
            'SQLSTATE[08',  // Connection/communication errors
            'SQLSTATE[57',  // Operator errors
            'SQLSTATE[58',  // System errors
        ];

        foreach ($retryablePatterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
