<?php
declare(strict_types=1);
namespace Modules\TraceForward\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialDocument extends Model
{
    protected $connection = 'eudr_ts';
    protected $table = 't_material_document';
    protected $primaryKey = 'id_matdoc';
    public $timestamps = true;

    protected $fillable = [
        'id_trace_head',
        'material_document',
        'po_so',
        'created_by',
        'updated_by',
    ];

    public function traceHeader()
    {
        return $this->belongsTo(TraceHeader::class, 'id_trace_head', 'id_trace_head');
    }
}
