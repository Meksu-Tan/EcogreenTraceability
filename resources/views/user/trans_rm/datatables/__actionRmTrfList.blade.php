@if ($model->traced == 'N/A')
    @if ($model->status == '0')
        <a href="#"
            data-href="{{ $activate_url }}"
            id="activate-trf-entry"
            class="btn btn-icon btn-info btn-sm"
            title="Activate"
            style="font-size: 9px;">
                <i class="	fas fa-undo"></i>
        </a>
    @else
        <a href="#"
            data-href="{{ $destroy_url }}"
            id="destroy-trf-entry"
            class="btn btn-icon btn-danger btn-sm"
            title="De-Activate"
            style="font-size: 10px;">
                <i class="	fas fa-trash"></i>
        </a>

        <!-- <a href="#"
            data-href="{{ $update_url }}"
            data-idHeader="{{ $model->id_balance_head }}"
            data-idTank="{{ $model->id_tank }}"
            data-idMaterial="{{ $model->id_material }}"
            data-idRmNumber="{{ $model->trace_no }}"
            data-entryDate="{{ $model->entry_date }}"
            data-materialDoc="{{ $model->material_document }}"
            data-status="{{ $model->status }}"
            data-po="{{ $model->po_so }}"
            id="update-rm-entry"
            class="btn btn-icon btn-warning btn-sm"
            title="Update"
            style="font-size: 10px;">
                <i class="fas fa-pencil-alt"></i>
        </a> -->
    @endif
@endif
