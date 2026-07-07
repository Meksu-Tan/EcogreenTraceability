<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        // DISABLED: id_sloc still actively used by code.
        // Dropping would break Feed, Rundown, and all transaction repos.

    }

    public function down(): void
    {
        // Irreversible
    }
};
