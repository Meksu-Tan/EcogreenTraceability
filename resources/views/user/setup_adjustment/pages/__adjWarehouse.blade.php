@extends('layouts.app_user')
@section('title', 'Adjustment - WAREHOUSE')
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
                                <button type="button" id="adjustment-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> New Adjustment </button>
                                <button type="button" id="adjustment-init" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> Stock Initialization </button>
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
                                            <th>Batch No</th>
                                            <th>Po No</th>
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
                    initialize_adjustment('ADD', 'post_storeAdjustmentWhx');
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
                    initialize_modalAdjustmentInit('post_adjustmentInitWhx', 'ADD');
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
                        flag: 'get_dtAdjustmentWhx'
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
                    { data: 'batch_no', name: 'batch_no', className: 'text-center'},
                    { data: 'po_no', name: 'po_no', className: 'text-center'},
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
