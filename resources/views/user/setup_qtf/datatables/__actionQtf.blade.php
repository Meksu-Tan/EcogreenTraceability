@if ($model->status == '0')
    <a href="#"
        data-href="{{ $activate_url }}"
        id="quantifier-activate"
        class="btn btn-icon btn-info btn-sm"
        title="Activate"
        style="font-size: 9px;">
            <i class="fas fa-undo"></i>
    </a>
@else
    <a href="#"
        data-href="{{ $destroy_url }}"
        id="quantifier-destroy"
        class="btn btn-icon btn-danger btn-sm"
        title="De-Activate"
        style="font-size: 10px;">
            <i class="fas fa-trash"></i>
    </a>

    <a href="#"
        data-href="{{ $update_url }}"
        data-id="{{ $model->id_reset }}"
        data-flowmeter="{{ $model->flowmeter }}"
        data-remark="{{ $model->remark }}"
        data-resetDate="{{ $model->reset_date }}"
        data-value="{{ $model->value }}"
        data-status="{{ $model->status }}"
        id="quantifier-update"
        class="btn btn-icon btn-warning btn-sm"
        title="Update"
        style="font-size: 10px;">
            <i class="fas fa-pencil-alt"></i>
    </a>
@endif
