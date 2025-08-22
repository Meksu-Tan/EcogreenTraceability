@if (empty($model->po_no))
    <button class="btn btn-warning btn-sm" style="color:black"
            id="pck-addPoNo"
            title="Add"
            data-idWhxHead="{{ $model->id_whx_head }}">
        Add PO No
    </button>
@else
    {{ $model->po_no }} &nbsp;&nbsp;
    <button class="btn btn-warning btn-sm" style="color:white"
            id="pck-editPoNo"
            title="Edit"
            data-idWhxHead="{{ $model->id_whx_head }}"
            data-poNo="{{ $model->po_no }}">
    </button>
@endif
