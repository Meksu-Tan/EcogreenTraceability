@if ($model->status == '0')
    <a href="#"
        data-href="{{ $activate_url }}"
        id="material-activate"
        class="btn btn-icon btn-info btn-sm"
        title="Activate"
        style="font-size: 9px;">
            <i class="	fas fa-undo"></i>
    </a>
@else
    <a href="#"
        data-href="{{ $destroy_url }}"
        id="material-destroy"
        class="btn btn-icon btn-danger btn-sm"
        title="De-Activate"
        style="font-size: 10px;">
            <i class="	fas fa-trash"></i>
    </a>

    <a href="#"
        data-href="{{ $update_url }}"
        data-id="{{ $model->id_material }}"
        data-code="{{ $model->code }}"
        data-codeNonEudr="{{ $model->code_noneudr }}"
        data-description="{{ $model->description }}"
        data-yield="{{ $model->yield }}"
        data-type="{{ $model->type }}"
        data-qtffeed="{{ $model->qtf_feed }}"
        data-qtfrundown="{{ $model->qtf_rundown }}"
        data-statusPackaging="{{ $model->status_packaging }}"
        data-status="{{ $model->status }}"
        data-codeSupplier="{{ $model->code_matl_supplier }}"
        id="material-update"
        class="btn btn-icon btn-warning btn-sm"
        title="Update"
        style="font-size: 10px;">
            <i class="fas fa-pencil-alt"></i>
    </a>
@endif
