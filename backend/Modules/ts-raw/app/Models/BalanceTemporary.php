<?php

declare(strict_types=1);

namespace Modules\TsRaw\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Material\Models\Material;
use Modules\Supplier\Models\Supplier;

class BalanceTemporary extends Model
{
    protected $connection = 'eudr_ts';

    protected $table = 't_balance_temporary';

    protected $primaryKey = 'id_balance_temp';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'entry_no',
        'id_supplier',
        'id_material',
        'id_plant',
        'qty',
        'batch_sap',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'status' => 'integer',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'id_material', 'id_material');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeByEntryNo($query, $entryNo)
    {
        return $query->where('entry_no', $entryNo);
    }
}
