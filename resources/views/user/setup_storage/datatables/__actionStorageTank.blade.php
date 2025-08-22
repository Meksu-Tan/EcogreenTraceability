@if ($model->status == '0')
    <a href="#"
        data-href="{{ $activate_url }}"
        id="storageTank-activate"
        class="btn btn-icon btn-info btn-sm"
        title="Activate"
        style="font-size: 9px;">
            <i class="	fas fa-undo"></i>
    </a>
@else
    <a href="#"
        data-href="{{ $destroy_url }}"
        id="storageTank-destroy"
        class="btn btn-icon btn-danger btn-sm"
        title="De-Activate"
        style="font-size: 10px;">
            <i class="	fas fa-trash"></i>
    </a>

    <a href="#"
        data-href="{{ $update_url }}"
        data-id="{{ $model->id_tank }}"
        data-code="{{ $model->code }}"
        data-code2="{{ $model->code_2 }}"
        data-code3="{{ $model->code_3 }}"
        data-code4="{{ $model->code_4 }}"
        data-description="{{ $model->description }}"
        data-idPlant="{{ $model->id_plant }}"
        data-status="{{ $model->status }}"
        id="storageTank-update"
        class="btn btn-icon btn-warning btn-sm"
        title="Update"
        style="font-size: 10px;">
            <i class="fas fa-pencil-alt"></i>
    </a>

    <a href="#"
        data-id="{{ $model->id_tank }}"
        data-storage="{{ $model->storage }}"
        id="storageTank-view"
        class="btn btn-icon btn-primary btn-sm"
        title="View Detail"
        style="font-size: 10px;">
            <i class="fas fa-bars"></i>
    </a>
@endif


