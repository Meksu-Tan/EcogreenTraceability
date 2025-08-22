@if (empty($model->batch_no))
    <button class="btn btn-warning btn-sm" style="color:black"
            id="pck-addBatchNo"
            title="Add"
            data-idWhxHead="{{ $model->id_whx_head }}">
        Add BATCH No
    </button>
@else
    {{ $model->batch_no }} &nbsp;&nbsp;
    <button class="btn btn-warning btn-sm" style="color:white"
            id="pck-editBatchNo"
            title="Edit"
            data-idWhxHead="{{ $model->id_whx_head }}"
            data-batchNo="{{ $model->batch_no }}"
            data-idSection="{{ $model->id_section }}">
    </button>
@endif
