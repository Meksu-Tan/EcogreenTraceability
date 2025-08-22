@if (empty($model->po_so))
    <button class="btn btn-warning btn-sm" style="color:black"
            id="poso-addDocNo"
            title="Add"
            data-idTraceHead="{{ $model->id_trace_head }}">
        Add PO No
    </button>
@else
    {{ $model->po_so }} &nbsp;&nbsp;
    <button class="btn btn-warning btn-sm" style="color:white"
            id="poso-editDocNo"
            title="Edit"
            data-idTraceHead="{{ $model->id_trace_head }}"
            data-number="{{ $model->po_so }}">
    </button>
@endif
