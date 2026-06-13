<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: m_sloc_detail dihapus, diganti m_sloc flat
    }

    public function down(): void
    {
        Schema::dropIfExists('m_sloc_detail');
    }
};
