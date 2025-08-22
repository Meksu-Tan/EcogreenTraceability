<a href="#"
    data-idHeader="{{ $model->id_balance_head }}"
    data-traceNo="{{ $model->trace_no }}"
    data-idMaterial="{{ $model->id_material }}"
    id="view-backward-trace"
    class="btn btn-icon btn-primary btn-sm"
    title="View Trace"
    style="font-size: 10px;">
        <i class="fab fa-empire"></i>
</a>
<a href="#"
    data-batchNo="{{ $model->batch_no }}"
    data-traceNo="{{ $model->trace_no }}"
    data-soNo="{{ $model->so_no }}"
    id="view-shipment-detail"
    class="btn btn-icon btn-info btn-sm"
    title="View Shipment Detail"
    style="font-size: 10px;">
        <i class="fab fa-dashcube"></i>
</a>
<a href="#"
    data-batchNo="{{ $model->batch_no }}"
    id="view-shipment-batch"
    class="btn btn-icon btn-light btn-sm"
    title="View Batch Packaging"
    style="font-size: 10px;">
        <i class="fab fa-periscope"></i>
</a>
