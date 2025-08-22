@if (!is_null($model->adjust_flag))
    @if ($model->adjust_flag == '1')
        <a href="#"
            data-href="{{ $destroy_url }}"
            id="adjustment-destroy"
            class="btn btn-icon btn-danger btn-sm"
            title="Delete"
            style="font-size: 10px;">
                <i class="	fas fa-trash"></i>
        </a>
    @endif
@endif
