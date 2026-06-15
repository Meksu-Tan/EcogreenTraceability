<?php declare(strict_types=1);

namespace Modules\Shared\Traits;

use Illuminate\Support\Facades\DB;

trait TransactionLoggerTrait
{
    /**
     * Log a transaction action into log_transactions table.
     *
     * @param string $module The module name (e.g., T_MATERIAL_DOCUMENT, BLENDING_ENTRY)
     * @param string $type The action type (e.g., ADD, UPDATE, DE-ACTIVATE)
     * @param string $description The description of the action
     * @param string $user The user performing the action
     * @param string $connection The database connection to use (default: eudr_ts)
     */
    public function logTransaction(string $module, string $type, string $description, string $user, string $connection = 'eudr_ts'): void
    {
        DB::connection($connection)->table('log_transactions')->insert([
            'log_module' => $module,
            'log_type' => $type,
            'log_description' => $description,
            'created_by' => $user,
            'created_at' => now(),
        ]);
    }
}
