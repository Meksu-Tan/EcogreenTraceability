@if ($model->status == '0')
    <a href="#"
        data-href="{{ $activate_url }}"
        id="matlPck-activate"
        class="btn btn-icon btn-info btn-sm"
        title="Activate"
        style="font-size: 9px;">
            <i class="	fas fa-undo"></i>
    </a>
@else
    <a href="#"
        data-href="{{ $destroy_url }}"
        id="matlPck-destroy"
        class="btn btn-icon btn-danger btn-sm"
        title="De-Activate"
        style="font-size: 10px;">
            <i class="	fas fa-trash"></i>
    </a>

    <a href="#"
        data-id="{{ $model->id_materialpck }}"
        data-code="{{ $model->code }}"
        data-codeNonEudr="{{ $model->code_noneudr }}"
        data-description="{{ $model->description }}"
        data-idMaterial="{{ $model->id_material }}"
        id="matlPck-update"
        class="btn btn-icon btn-warning btn-sm"
        title="Update"
        style="font-size: 10px;">
            <i class="fas fa-pencil-alt"></i>
    </a>
@endif
