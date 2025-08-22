@if (empty($model->next_process))
        <a href="#"
            data-idShipHead="{{ $model->id_ship_head }}"
            data-idTraceHead="{{ $model->id_trace_head }}"
            data-traceNo="{{ $model->trace_no }}"
            data-fromTraceNo="{{ $model->from_trace_no }}"
            data-soNo="{{ $model->so_no }}"
            id="ship-cancel"
            class="btn btn-icon btn-danger btn-sm"
            title="Cancel"
            style="font-size: 10px; color:black">
                <i class="fas fa-trash"></i>
        </a>
@endif

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
    data-soNo="{{ $model->so_no }}"
    id="view-shipment-batch"
    class="btn btn-icon btn-primary btn-sm"
    title="View Batch Packaging"
    style="font-size: 10px;">
        <i class="fab fa-periscope"></i>
</a>

<a href="#"
    data-docUrl="{{ $model->doc_url }}"
    data-traceNo="{{ $model->trace_no }}"
    id="view-shipment-doc"
    class="btn btn-icon btn-warning btn-sm"
    title="View Document"
    style="font-size: 10px;">
        <i class="fas fa-file"></i>
</a>
