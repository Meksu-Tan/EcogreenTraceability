@if (empty($model->next_process))
        <a href="#"
            data-href="{{ $destroy_url }}"
            id="destroy-blending-entry"
            class="btn btn-icon btn-danger btn-sm"
            title="De-Activate"
            style="font-size: 10px;">
                <i class="	fas fa-trash"></i>
        </a>
@endif
