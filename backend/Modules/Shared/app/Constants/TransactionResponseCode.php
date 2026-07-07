<?php

declare(strict_types=1);

namespace Modules\Shared\Constants;

class TransactionResponseCode
{
    public const SUCCESS = 1;

    public const GENERIC_FAILURE = 0;

    public const DUPLICATE_ENTRY = 2;

    public const INSUFFICIENT_BALANCE = 3;

    public const INSUFFICIENT_STOCK = 4;

    public const NO_SUPPLIER_TRACED = 6;

    public const DUPLICATE_TRACE_NUMBER = 7;

    public const PERIOD_LOCKED = 99;

    // Add other constants as needed based on frontend needs
}
