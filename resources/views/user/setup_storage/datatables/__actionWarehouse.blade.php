@if ($model->status == '0')
    <a href="#"
        data-href="{{ $activate_url }}"
        id="warehouse-activate"
        class="btn btn-icon btn-info btn-sm"
        title="Activate"
        style="font-size: 9px;">
            <i class="	fas fa-undo"></i>
    </a>
@else
    <a href="#"
        data-href="{{ $destroy_url }}"
        id="warehouse-destroy"
        class="btn btn-icon btn-danger btn-sm"
        title="De-Activate"
        style="font-size: 10px;">
            <i class="	fas fa-trash"></i>
    </a>

    <a href="#"
        data-href="{{ $update_url }}"
        data-id="{{ $model->id_warehouse }}"
        data-idBatch="{{ $model->id_batch }}"
        data-code="{{ $model->code }}"
        data-description="{{ $model->description }}"
        id="warehouse-update"
        class="btn btn-icon btn-warning btn-sm"
        title="Update"
        style="font-size: 10px;">
            <i class="fas fa-pencil-alt"></i>
    </a>

@endif


