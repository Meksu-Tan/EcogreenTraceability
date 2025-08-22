@extends('layouts.app_user')
@section('title', 'Adjustment - WIP')
@section('content')
<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @include('user.setup_adjustment.partials.__buttonHeader')
                    </div>
                </div>
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="adjustment-add" class="btn btn-primary" style="color:white"><i class="fab fa-fly" aria-hidden="true"></i> Adjustment Last Record </button>
                                <button type="button" id="adjustment-init" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> Stock Initialization </button>
                                <button type="button" id="adjustment-period" class="btn btn-primary" style="color:white"><i class="fab fa-ethereum" aria-hidden="true"></i> Period Adjustment </button>

                                <!-- <button type="button" id="email" class="btn btn-primary" style="color:white"><i class="fab fa-ethereum" aria-hidden="true"></i> Trial Email </button> -->
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-adjustment" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Adjustment No</th>
                                            <th>Material No</th>
                                            <th>Entry Date</th>
                                            <th>Material</th>
                                            <th>SLoc</th>
                                            <th>Adjusted Batch</th>
                                            <th>Adjustment (MT)</th>
                                            <th>Supplier / Batch SAP / Adjustment (MT)</th>
                                            <th>Created At</th>
                                            <th>Created By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
@push('js')
<!-- MODAL -->
    @include('user.setup_adjustment.modals.__adjustmentModal')
    @include('user.setup_adjustment.modals.__adjustmentMatlDocModal')
    @include('user.setup_adjustment.modals.__adjustmentInitModal')
    @include('user.setup_adjustment.modals.__adjustmentInitSupplierModal')
    @include('user.setup_adjustment.modals.__adjustmentPeriodHeaderModal')
    @include('user.setup_adjustment.modals.__adjustmentPeriodDetailModal')
    @include('user.setup_adjustment.modals.__adjustmentPeriodNewModal')
    @include('user.setup_adjustment.modals.__adjustmentPeriodViewModal')


    @include('user.setup_adjustment.modals.__adjustmentPeriodUploadExcelModal')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('adjustment.index') }}";
        var post_url    = "{{ route('adjustment.store') }}";
        var show_url    = "{{ route('adjustment.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $dt_adjustment                    = '#table-adjustment';

        const $btn_adjustmentAdd                = '#adjustment-add';
        const $btn_adjustmentDelete             = '#adjustment-destroy';
        const $btn_adjustmentAddMatlDoc         = '#adjustment-addDocNo';
        const $btn_adjustmentEditMatlDoc        = '#adjustment-editDocNo';
        const $btn_adjustmentInit               = '#adjustment-init';

        const $modal_adjustment                 = '#modal-adjustment';
        const $modal_adjustmentMatlDoc          = '#modal-materialdoc-add';
        const $modal_adjustmentInit             = '#modal-adjustmentInit';
        const $modal_adjustmentInit_supplier    = '#modal-adjustmentInitSupplier';
        const $modal_adjustmentPeriodHeader     = '#modal-adjPeriodHeader';
        const $modal_adjustmentPeriodDetail     = '#modal-adjPeriodDetail';
        const $modal_adjustmentPeriodNew        = '#modal-adjPeriodNew';
        const $modal_adjustmentPeriodUpload     = '#modal-adjPeriodUpload';
        const $modal_adjustmentPeriodView       = '#modal-adjPeriodView';

        const $btn_adjustmentPeriod             = '#adjustment-period';
        const $btn_email                        = '#email';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_adjustmentAddMatlDoc, function(){
                    var $idTraceHead = $(this).attr('data-idTraceHead');

                    $($modal_adjustmentMatlDoc).modal('show');
                    $($txt_materialdoc_flag).val('post_matlDocNumber');
                    $($txt_materialdoc_mode).val('ADD');
                    $($txt_materialdoc_id).val($idTraceHead);
                });
                $(document).on('click', $btn_adjustmentEditMatlDoc, function(){
                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $docNumber = $(this).attr('data-number');

                    $($modal_adjustmentMatlDoc).modal('show');
                    $($txt_materialdoc_flag).val('post_matlDocNumber');
                    $($txt_materialdoc_mode).val('UPDATE');
                    $($txt_materialdoc_id).val($idTraceHead);
                    $($txt_materialdoc_number).val($docNumber);
                });
                $(document).on('click', $btn_adjustmentAdd, function(){
                    $($modal_adjustment).modal('show');
                    initialize_adjustment('ADD', 'post_storeAdjustment');
                });
                $(document).on('click', $btn_adjustmentDelete, function(){
                    var $href = $(this).attr('data-href');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete this data',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes!'
                    }).then((willDeleted) => {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        if (willDeleted.value) {
                            ajax_deleteAdjustment($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_adjustmentInit, function(){
                    $($modal_adjustmentInit).modal('show');
                    initialize_modalAdjustmentInit('post_adjustmentInit', 'ADD');
                });
                $(document).on('click', $btn_adjustmentPeriod, function(){
                    $($modal_adjustmentPeriodHeader).modal('show');
                    initialize_adjustmentPeriodHeader();
                });

                $(document).on('click', $btn_email, function(){
                    let name = "Santo Wijaya";
                    let email = "santo.wijaya@ecogreenoleo.com";

                    if (name === '' || email === '') {
                        $('#statusMessage').css('color', 'red').text('Nama dan Email harus diisi!');
                        return;
                    }

                    $.ajax({
                        url: '{{ url("/send-email") }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            name: name,
                            email: email
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#statusMessage').css('color', 'green').text(response.message);
                            } else {
                                $('#statusMessage').css('color', 'red').text('Gagal mengirim email.');
                            }
                        },
                        error: function(xhr) {
                            let errorMsg = 'Terjadi kesalahan.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            $('#statusMessage').css('color', 'red').text(errorMsg);
                        }
                    });
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateDtAdjustment($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtAdjustment'
                    }
                },
                order: [[ 0, 'desc']],
                responsive: true,
                columnDefs: [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'adjust_no', name: 'adjust_no', className: 'text-center'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left', width:'15%'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'trace_no', name: 'trace_no', className: 'text-center'},
                    { data: 'adjustment', name: 'adjustment', className: 'text-right', width:'10%'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'30%'},
                    { data: 'created_at', name: 'created_at', className: 'text-center'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                ]
            });
        };
        function ajax_deleteAdjustment($href){
            $.ajax({
                url: $href,
                type: "POST",
                data: {
                    '_method': 'DELETE'
                },
                success: function(data) {
                    if (data.status == 1) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 500
                        })
                        $($dt_adjustment).DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        })
                    }
                }
            })
        };

    /* FUNCTION DYNAMICS */
        function initialize_page(){
            ajax_populateDtAdjustment($dt_adjustment);
        };

    /* FUNCTION AUTO-REFRESH */

</script>
@endpush
