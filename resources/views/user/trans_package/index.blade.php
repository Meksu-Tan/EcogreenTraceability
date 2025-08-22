@extends('layouts.app_user')
@section('title', 'Packaging Entry')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <!-- <div class="card">
                    <div class="card-body">
                        @include('user.trans_package.partials.__buttonHeader')
                    </div>
                </div> -->
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="pck-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> New Packaging Entry </button>
                                <button type="button" class="btn btn-danger">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-pck" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Entry Date</th>
                                            <th>Trace No (From >>> To)</th>
                                            <th>PO</th>
                                            <th>PPH Batch No</th>
                                            <th>WIP Product</th>
                                            <th>FG Product</th>
                                            <th>FG Sloc</th>
                                            <th>Init Material (MT)</th>
                                            <th>Init Supplier (MT)</th>
                                            <th>Balance (MT)</th>
                                            <th>Supplier / Batch SAP / Init (MT) / Balance (MT)</th>
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
    @include('user.trans_package.modals.__pckEntryModal')
    @include('user.trans_package.modals.__pckEntryPoModal')
    @include('user.trans_package.modals.__pckEntryBatchModal')
    @include('user.trans_shipment.modals.__shipBatchPackagingModal')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('packageentry.index') }}";
        var post_url    = "{{ route('packageentry.store') }}";
        var show_url    = "{{ route('packageentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $modal_pckEntry               = '#modal-pckEntry';
        const $modal_pckEntryPo             = '#modal-pckEntryPo';
        const $modal_pckEntryBatch          = '#modal-pckEntryBatch';
        const $modal_shipEntryBatch         = '#modal-shipEntryBatch';

        const $dt_pckEntry                  = '#table-pck';
        const $btn_pckEntry_add             = '#pck-add';
        const $btn_pckEntry_delete          = '#pck-cancel';
        const $btn_pckEntry_viewBalance     = '#pck-balance';

        const $btn_pckEntry_addPo           = '#pck-addPoNo';
        const $btn_pckEntry_editPo          = '#pck-editPoNo';

        const $btn_pckEntry_addBatch        = '#pck-addBatchNo';
        const $btn_pckEntry_editBatch       = '#pck-editBatchNo';

        const $btn_shipEntry_batchDetail    = '#view-shipment-batch';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_pckEntry_delete, function(){
                    var $idWhxHead = $(this).attr('data-idWhxHead');
                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $traceNo = $(this).attr('data-traceNo');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete batch no. ' + $traceNo,
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
                            ajax_postCancelPck($idWhxHead, $idTraceHead, $traceNo);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_pckEntry_add, function(){
                    $($modal_pckEntry).modal('show');
                    initialize_pkcEntry('post_entryPck', 'ADD');
                });
                $(document).on('click', $btn_pckEntry_addPo, function(){
                    var $idWhxHead = $(this).attr('data-idWhxHead');

                    $($modal_pckEntryPo).modal('show');
                    initialize_pckEntryPo('post_pckEntry_poNo', 'ADD', $idWhxHead);
                });
                $(document).on('click', $btn_pckEntry_editPo, function(){
                    var $idWhxHead = $(this).attr('data-idWhxHead');
                    var $poNo = $(this).attr('data-poNo');

                    $($modal_pckEntryPo).modal('show');
                    initialize_pckEntryPo('post_pckEntry_poNo', 'UPDATE', $idWhxHead, $poNo);
                });
                $(document).on('click', $btn_pckEntry_addBatch, function(){
                    var $idWhxHead = $(this).attr('data-idWhxHead');

                    $($modal_pckEntryBatch).modal('show');
                    initialize_pckEntryBatch('post_pckEntry_batchNo', 'ADD', $idWhxHead);
                });
                $(document).on('click', $btn_pckEntry_editBatch, function(){
                    var $idWhxHead = $(this).attr('data-idWhxHead');
                    var $batchNo = $(this).attr('data-batchNo');
                    var $idSection = $(this).attr('data-idSection');

                    $($modal_pckEntryBatch).modal('show');
                    initialize_pckEntryBatch('post_pckEntry_batchNo', 'UPDATE', $idWhxHead, $batchNo, $idSection);
                });
                $(document).on('click', $btn_shipEntry_batchDetail, function(){
                    var $batchNo = $(this).attr('data-batchNo');

                    $($modal_shipEntryBatch).modal('show');
                    initialize_shipEntryBatch_modal($batchNo);
                });
        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function ajax_populatePckEntry($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtPckEntry'
                    }
                },
                order: [[ 0, 'asc']],
                responsive: true,
                columnDefs: [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                },
                {
                    targets: [9,10], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        if (rowData.init_qty === rowData.balance_supplier) {
                            $(td).css('color', 'green');
                        } else {
                            $(td).css('color', 'red');
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'fromto_trace_no', name: 'fromto_trace_no', className: 'text-center'},
                    { data: 'po_no', name: 'po_no', className: 'text-center'},
                    { data: 'batch_no', name: 'batch_no', className: 'text-center'},
                    { data: 'feed', name: 'feed', className: 'text-left'},
                    { data: 'fg', name: 'fg', className: 'text-left'},
                    { data: 'whx', name: 'whx', className: 'text-center'},
                    { data: 'init_qty', name: 'init_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'balance', name: 'balance', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        };
        function ajax_postCancelPck($idWhxHead, $idTraceHead, $traceNo){
            $.ajax({
                url: post_url,
                method: "POST",
                dataType: "JSON",
                data:{
                    flag: 'post_cancelPck',
                    idWhxHead: $idWhxHead,
                    idTraceHead: $idTraceHead,
                    traceNo: $traceNo,
                },
                success: function(data) {
                    if (data.status == 1) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 500
                            });

                        initialize_page();

                    } else {
                        Swal.fire(data.message, "", "error");
                    }
                }
            });
        };

    /* FUNCTION DYNAMICS */



    /* FUNCTION INITIALIZATION */
        function initialize_page(){
            ajax_populatePckEntry($dt_pckEntry);
        };

    /* FUNCTION AUTO-REFRESH */



</script>
@endpush
