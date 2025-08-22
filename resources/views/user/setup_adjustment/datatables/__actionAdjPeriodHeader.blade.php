@if ($model->status == '1')
    @if ($model->adjust_status == '0')
        @if ($model->uploaded_file == '0')
            <a href="#"
                data-href="{{ $destroy_url }}"
                id="adjustmentPeriodHeader-destroy"
                class="btn btn-icon btn-danger btn-sm"
                title="Delete"
                style="font-size: 10px;">
                    <i class="fas fa-trash"></i>
            </a>
            <a href="#"
                data-id="{{ $model->id_report_head }}"
                data-batch="{{ $model->batch_sap }}"
                data-period="{{ $model->period }}"
                id="adjustmentPeriodHeader-upload"
                class="btn btn-icon btn-primary btn-sm"
                title="Upload Excel"
                style="font-size: 10px;">
                    <i class="fas fa-file-alt"></i>
            </a>
        @endif

        <a href="#"
            data-href="{{ $update_url }}"
            data-id="{{ $model->id_report_head }}"
            data-batch="{{ $model->batch_sap }}"
            data-period="{{ $model->period }}"
            id="adjustmentPeriodHeader-update"
            class="btn btn-icon btn-warning btn-sm"
            title="Update"
            style="font-size: 10px;">
                <i class="fas fa-pencil-alt"></i>
        </a>

    @endif

    @if ($model->uploaded_file == '1')
        <a href="#"
            data-id="{{ $model->id_report_head }}"
            data-batch="{{ $model->batch_sap }}"
            data-period="{{ $model->period }}"
            data-adjustStatus="{{ $model->adjust_status }}"
            id="adjustmentPeriodHeader-view"
            class="btn btn-icon btn-info btn-sm"
            title="View Detail"
            style="font-size: 10px; color:white">
                <i class="fas fa-anchor"></i>
        </a>
    @endif

    @if ($model->lock_status == '1')
        <a href="#"
            data-id="{{ $model->id_report_head }}"
            id="adjustmentPeriodHeader-unlock"
            class="btn btn-icon btn-warning btn-sm"
            title="Unlock Period"
            style="font-size: 10px; color:white">
                <i class="fa fa-unlock"></i>
        </a>
    @else
    <a href="#"
            data-id="{{ $model->id_report_head }}"
            id="adjustmentPeriodHeader-lock"
            class="btn btn-icon btn-primary btn-sm"
            title="Lock Period"
            style="font-size: 10px; color:white">
                <i class="fa fa-lock"></i>
        </a>
    @endif

@endif
