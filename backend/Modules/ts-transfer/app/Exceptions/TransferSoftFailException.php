<?php

declare(strict_types=1);

namespace Modules\TsTransfer\Exceptions;

use Exception;

/**
 * Signals a business-rule failure (non-1 response) from inside a
 * DB::transaction() closure so the transaction rolls back without
 * being treated as an unexpected error by the outer catch block.
 */
class TransferSoftFailException extends Exception
{
    public function __construct(public readonly array $result)
    {
        parent::__construct('Transfer soft-fail: response '.($result['response'] ?? 'unknown'));
    }
}
