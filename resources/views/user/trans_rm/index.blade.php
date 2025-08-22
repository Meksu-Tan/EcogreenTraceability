@extends('layouts.app_user')
@section('title', 'Raw Material Lists')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="new-rm-entry" class="btn btn-primary" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> New RM Entry </button>
                                <button type="button" id="new-rm-trf" class="btn btn-primary" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> Transfer to Feed Tank </button>
                                <button type="button" class="btn btn-danger">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <h4 class="card-header-title" style="padding-left:25px">
                                <b>STORAGE TANK LOG</b>
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-rm-list" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Trace No</th>
                                            <th>Entry Date</th>
                                            <th>Matl Doc</th>
                                            <th>PurchO</th>
                                            <th>Material</th>
                                            <th>Sloc</th>
                                            <th>Init Material (MT)</th>
                                            <th>Init Supplier (MT)</th>
                                            <th>On-Hand (MT)</th>
                                            <th>Supplier / Batch SAP / Init Qty (MT) / Remark</th>
                                            <th>Status</th>
                                            <th>Created at</th>
                                            <th>Created by</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <h4 class="card-header-title" style="padding-left:25px">
                                <b>FEED TANK LOG</b>
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-trf-list" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>TraceNo (From >>> To)</th>
                                            <th>Entry Date</th>
                                            <th>Matl Doc</th>
                                            <th>Material</th>
                                            <th>Sloc</th>
                                            <th>Init Material (MT)</th>
                                            <th>Init Supplier (MT)</th>
                                            <th>On-Hand (MT)</th>
                                            <th>Supplier / Batch SAP / Init Qty (MT) / Remark</th>
                                            <th>Status</th>
                                            <th>Created at</th>
                                            <th>Created by</th>
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
    @include('user.trans_rm.modals.__rmEntryModal')
    @include('user.trans_rm.modals.__rmEntryDetailModal')
    @include('user.trans_rm.modals.__rmEntryTransfer')
    @include('user.trans_rm.modals.__rmEntryTransferDetail')
    @include('user.trans_rm.modals.__addMaterialDocModal')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('rmentry.index') }}";
        var post_url    = "{{ route('rmentry.store') }}";
        var show_url    = "{{ route('rmentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $btn_newRmEntry               = '#new-rm-entry';
        const $btn_deactivateRmEntry        = '#destroy-rm-entry';
        const $btn_activateRmEntry          = '#activate-rm-entry';
        const $btn_updateRmEntry            = '#update-rm-entry';

        const $tbl_RmList                   = '#table-rm-list';
        const $tbl_RmListTrf                = '#table-trf-list';

        const $modal_rmEntry                = '#modal-rm-entry';
        const $modal_rmEntry_addSupplier    = '#modal-rm-entrySupplier';
        const $modal_rmEntryTrf             = '#modal-rm-trf';
        const $modal_rmEntryTrf_addMaterial = '#modal-rm-trf-entryMaterial';
        const $modal_addMaterialDoc         = '#modal-materialdoc-add';

        const $btn_newRmTrfEntry            = '#new-rm-trf';
        const $btn_poso_addDocNo            = '#poso-addDocNo';
        const $btn_poso_editDocNo           = '#poso-editDocNo';
        const $btn_sap_addDocNo             = '#sap-addDocNo';
        const $btn_sap_editDocNo            = '#sap-editDocNo';

        const $btn_activateRmTrfEntry       = '#activate-trf-entry';
        const $btn_deactivateRmTrfEntry     = '#destroy-trf-entry';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_newRmEntry, function(e){
                    e.preventDefault();

                    $($modal_rmEntry).modal('show');
                    initialize_modalRmEntry('ADD');
                });
                $(document).on('click', $btn_deactivateRmEntry, function(e){
                    e.preventDefault();

                    var $href = $(this).attr('data-href');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'De-Activate this data',
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
                            ajax_deactivateRmEntry($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_activateRmEntry, function(e){
                    e.preventDefault();
                    var $href = $(this).attr('data-href');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Activate this data',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes!'
                    }).then((willActivate) => {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        if (willActivate.value) {
                            ajax_activateRmEntry($href);
                        } else {
                            console.log(`data was dismissed by ${willActivate.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_updateRmEntry, function(e){
                    e.preventDefault();

                    var $idHead = $(this).attr('data-idHeader');
                    var $idTank = $(this).attr('data-idTank');
                    var $entryNo = $(this).attr('data-idRmNumber');
                    var $entryDate = $(this).attr('data-entryDate');
                    var $materialDoc = $(this).attr('data-materialDoc');
                    var $idMaterial = $(this).attr('data-idMaterial');
                    var $po = $(this).attr('data-po');

                    $($modal_rmEntry).modal('show');
                    initialize_modalRmEntry('UPDATE', $entryNo, $idHead, $entryDate, $idTank, $materialDoc, $idMaterial, $po);
                });
                $(document).on('click', $btn_newRmTrfEntry, function(e){
                    e.preventDefault();

                    $($modal_rmEntryTrf).modal('show');
                    initialize_modalRmTrfEntry('ADD');
                });
                $(document).on('click', $btn_deactivateRmTrfEntry, function(e){
                    e.preventDefault();

                    var $href = $(this).attr('data-href');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'De-Activate this data',
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
                            ajax_deactivateRmEntryTrf($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_sap_addDocNo, function(e){
                    e.preventDefault();

                    var $idTraceHead = $(this).attr('data-idTraceHead');

                    $($modal_addMaterialDoc).modal('show');
                    initialize_materialdoc_modal('post_matlDocNumber', 'ADD', 'Material Document (SAP)', $idTraceHead);
                });
                $(document).on('click', $btn_sap_editDocNo, function(e){
                    e.preventDefault();

                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $docNumber = $(this).attr('data-number');

                    $($modal_addMaterialDoc).modal('show');
                    initialize_materialdoc_modal('post_matlDocNumber', 'UPDATE', 'Material Document (SAP)', $idTraceHead, $docNumber);
                });
                $(document).on('click', $btn_poso_addDocNo, function(e){
                    e.preventDefault();

                    var $idTraceHead = $(this).attr('data-idTraceHead');

                    $($modal_addMaterialDoc).modal('show');
                    initialize_materialdoc_modal('post_matlDocNumber_po', 'ADD', 'Purchase Order (PO)', $idTraceHead);
                });
                $(document).on('click', $btn_poso_editDocNo, function(e){
                    e.preventDefault();

                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $docNumber = $(this).attr('data-number');

                    $($modal_addMaterialDoc).modal('show');
                    initialize_materialdoc_modal('post_matlDocNumber_po', 'UPDATE', 'Purchase Order (PO)', $idTraceHead, $docNumber);
                });
        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_dtRmList($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtRmList'
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
                },{
                    "targets": [12],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-check" style="color: green" title="Active"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-times" style="color: red" title="Not Active"></i>';
                        }
                    },
                },
                {
                    targets: [8,9], // index of 'balance_supplier' column
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
                    { data: 'action', name: 'action', orderable: false, searchable: false},
                    { data: 'trace_no', name: 'trace_no', className: 'text-center'},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'po_so', name: 'po_so', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'tf_number', name: 'tf_number', className: 'text-center'},
                    { data: 'init_qty', name: 'init_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'},
                    { data: 'status', name: 'status', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                ]
            });
        };
        function ajax_dtRmListTrf($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtRmListTrf'
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
                },{
                    "targets": [11],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-check" style="color: green" title="Active"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-times" style="color: red" title="Not Active"></i>';
                        }
                    },
                },
                {
                    targets: [7,8],
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
                    { data: 'action', name: 'action', orderable: false, searchable: false},
                    { data: 'trace_no', name: 'trace_no', className: 'text-right'},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'tf_number', name: 'tf_number', className: 'text-center'},
                    { data: 'init_qty', name: 'init_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'},
                    { data: 'status', name: 'status', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                ]
            });
        };
        function ajax_deactivateRmEntry($href){
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
                        $($tbl_RmList).DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        })
                    }
                }
            })
        };
        function ajax_activateRmEntry($href){
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
                        $($tbl_RmList).DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        })
                    }
                }
            })
        };
        function ajax_deactivateRmEntryTrf($href){
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
                        $($tbl_RmListTrf).DataTable().ajax.reload();
                        $($tbl_RmList).DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        })
                    }
                }
            })
        };

    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_page(){
            ajax_dtRmList($tbl_RmList);
            ajax_dtRmListTrf($tbl_RmListTrf);
        };

    /* FUNCTION AUTO-REFRESH */
        function time_format(d) {
            hours = format_two_digits(d.getHours());
            minutes = format_two_digits(d.getMinutes());
            seconds = format_two_digits(d.getSeconds());

            return hours + ":" + minutes + ":" + seconds;
        };
        function format_two_digits(n) {
            return n < 10 ? '0' + n : n;
        };

</script>
@endpush
