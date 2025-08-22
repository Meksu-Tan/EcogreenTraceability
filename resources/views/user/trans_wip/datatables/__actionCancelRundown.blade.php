@if (empty($model->next_process))
    @if ($model->is_last_row == 1)
        <a href="#"
            data-href="{{ $update_url }}"
            data-idTraceHead="{{ $model->id_trace_head }}"
            data-idBalanceHead="{{ $model->id_balance_head }}"
            data-traceNo="{{ $model->to_trace_no }}"
            id="rundown-cancel"
            class="btn btn-icon btn-danger btn-sm"
            title="Cancel"
            style="font-size: 10px;">
                <i class="fas fa-trash"></i>
        </a>
    @endif
@endif
