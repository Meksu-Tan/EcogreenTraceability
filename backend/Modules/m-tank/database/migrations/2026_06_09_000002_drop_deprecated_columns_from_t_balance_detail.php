<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $connection = 'eudr_ts';

    public function up(): void
    {
        // DISABLED: id_sloc is actively used by Feed::generalFeed(), Rundown::generalRundown(),
        // and all transaction modules. The column migration to tf_number was never completed.
        // Re-enable only after all readers/writers are migrated to tf_number.

    }

    public function down(): void
    {
        // Irreversible
    }
};
