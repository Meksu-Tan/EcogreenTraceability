<?php

declare(strict_types=1);

namespace Modules\Shared\Helpers;

/**
 * Standardized response codes used across modules.
 */
class ResponseCode
{
    /**
     * Response code indicating period is locked.
     */
    public const PERIOD_LOCKED = 99;

    /**
     * Response code indicating success.
     */
    public const SUCCESS = 1;

    /**
     * Response code indicating failure.
     */
    public const FAILED = 0;

    /**
     * Fallback date used when entry_date is missing.
     */
    public const FALLBACK_DATE = '2099-12-31';

    /**
     * Precision threshold for floating-point quantity comparisons.
     */
    public const QUANTITY_PRECISION_THRESHOLD = 0.0001;
}
