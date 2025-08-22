
    @if (empty($model->next_process))
        <a href="#"
            data-idWhxHead="{{ $model->id_whx_head }}"
            data-idTraceHead="{{ $model->id_trace_head }}"
            data-traceNo="{{ $model->trace_no }}"
            id="pck-cancel"
            class="btn btn-icon btn-danger btn-sm"
            title="Cancel"
            style="font-size: 10px; color:black">
                <i class="fas fa-trash"></i>
        </a>
    @endif

<a href="#"
    data-batchNo="{{ $model->batch_no }}"
    id="view-shipment-batch"
    class="btn btn-icon btn-primary btn-sm"
    title="View Batch Packaging"
    style="font-size: 10px;">
        <i class="fab fa-periscope"></i>
</a>
        <!-- <a href="#"
            data-idWhxHead="{{ $model->id_whx_head }}"
            data-idTraceHead="{{ $model->id_trace_head }}"
            data-idBalanceHead="{{ $model->id_balance_head }}"
            data-traceNo="{{ $model->trace_no }}"
            id="pck-balance"
            class="btn btn-icon btn-info btn-sm"
            title="Balance"
            style="font-size: 10px; color:black">
                <i class="fas fa-weight"></i>
        </a> -->
