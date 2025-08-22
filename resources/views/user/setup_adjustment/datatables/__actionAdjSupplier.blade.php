@if ($model->mode == 'UPDATE')
    <a href="#"
        data-href="{{ $update_url }}"
        data-entryNo="{{ $model->entry_no }}"
        data-qty="{{ $model->qty }}"
        data-idSupplier="{{ $model->id_supplier }}"
        data-supplier="{{ $model->supplier }}"
        data-idTail="{{ $model->idTail }}"
        data-batchSap="{{ $model->batch_sap }}"
        id="update-adjustmentInit-supplier"
        class="btn btn-icon btn-warning btn-sm"
        title="Update"
        style="font-size: 10px;">
            <i class="fas fa-pencil-alt"></i>
    </a>
@else
    <a href="#"
        data-href="{{ $destroy_url }}"
        id="destroy-adjustmentInit-supplier"
        class="btn btn-icon btn-danger btn-sm"
        title="Delete Supplier"
        style="font-size: 10px;">
            <i class="	fas fa-trash"></i>
    </a>
@endif
