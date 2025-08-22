@if (empty($model->material_document))
    <button class="btn btn-warning btn-sm" style="color:black"
            id="feed-addDocNo"
            title="Add"
            data-idTraceHead="{{ $model->id_trace_head }}">
        Add Doc No
    </button>
@else
    {{ $model->material_document }} &nbsp;&nbsp;
    <button class="btn btn-warning btn-sm" style="color:white"
            id="feed-editDocNo"
            title="Edit"
            data-idTraceHead="{{ $model->id_trace_head }}"
            data-number="{{ $model->material_document }}">
    </button>
@endif
