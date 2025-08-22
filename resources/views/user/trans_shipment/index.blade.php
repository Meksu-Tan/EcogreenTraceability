@extends('layouts.app_user')
@section('title', 'Shipment Entry')
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
                                <button type="button" id="ship-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> New Shipment Entry </button>
                                <button type="button" class="btn btn-danger">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-ship" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Entry Date</th>
                                            <th>Trace No (From >>> To)</th>
                                            <th>SO No</th>
                                            <th>Batch No</th>
                                            <th>FG / WIP Product</th>
                                            <th>Qty Material (MT)</th>
                                            <th>Qty Supplier (MT)</th>
                                            <th>Supplier / Batch SAP / Qty (MT)</th>
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
    @include('user.trans_shipment.modals.__shipEntryModal')
    @include('user.trans_shipment.modals.__shipEntrySoModal')
    @include('user.trans_shipment.modals.__shipBatchPackagingModal')
    @include('user.trans_shipment.modals.__shipDataShipmentModal')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('shipmententry.index') }}";
        var post_url    = "{{ route('shipmententry.store') }}";
        var show_url    = "{{ route('shipmententry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $modal_shipEntry               = '#modal-shipEntry';
        const $modal_shipEntrySo             = '#modal-shipEntrySo';
        const $modal_shipEntryBatch          = '#modal-shipEntryBatch';
        const $modal_shipDataShipment        = '#modal-shipData';

        const $dt_shipEntry                  = '#table-ship';
        const $btn_shipEntry_add             = '#ship-add';
        const $btn_shipEntry_delete          = '#ship-cancel';
        const $btn_shipEntry_viewBalance     = '#ship-balance';

        const $btn_shipEntry_addSo           = '#ship-addSoNo';
        const $btn_shipEntry_editSo          = '#ship-editSoNo';
        const $btn_shipEntry_shipDetail      = '#view-shipment-detail';
        const $btn_shipEntry_batchDetail     = '#view-shipment-batch';
        const $btn_shipView_document         = '#view-shipment-doc';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_shipEntry_delete, function(){
                    var $idShipHead = $(this).attr('data-idShipHead');
                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $traceNo = $(this).attr('data-traceNo');
                    var $fromTraceNo = $(this).attr('data-fromTraceNo');

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
                            ajax_postCancelShip($idShipHead, $idTraceHead, $traceNo, $fromTraceNo);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_shipEntry_add, function(){
                    $($modal_shipEntry).modal('show');
                    initialize_shipEntry('post_entryShip', 'ADD');
                });
                $(document).on('click', $btn_shipEntry_addSo, function(){
                    var $idShipHead = $(this).attr('data-idShipHead');

                    $($modal_shipEntrySo).modal('show');
                    initialize_shipEntrySo('post_shipEntry_soNo', 'ADD', $idShipHead);
                });
                $(document).on('click', $btn_shipEntry_editSo, function(){
                    var $idShipHead = $(this).attr('data-idShipHead');
                    var $soNo = $(this).attr('data-soNo');

                    $($modal_shipEntrySo).modal('show');
                    initialize_shipEntrySo('post_shipEntry_soNo', 'UPDATE', $idShipHead, $soNo);
                });
                $(document).on('click', $btn_shipEntry_batchDetail, function(){
                    var $batchNo = $(this).attr('data-batchNo');

                    $($modal_shipEntryBatch).modal('show');
                    initialize_shipEntryBatch_modal($batchNo);
                });
                $(document).on('click', $btn_shipEntry_shipDetail, function(){
                    var $batchNo = $(this).attr('data-batchNo');
                    var $soNo = $(this).attr('data-soNo');

                    $($modal_shipDataShipment).modal('show');
                    initialize_shipData_modal($soNo, $batchNo);
                });
                $(document).on('click', $btn_shipView_document, function(){
                    var fileName = $(this).attr('data-docUrl'); // Ambil nama file
                    var baseUrl = window.location.origin; // Dapatkan base URL
                    var fileUrl = baseUrl + "/eudr-ts/public/pdf/" + fileName; // Gabungkan dengan folder public/pdf/

                    window.open(fileUrl, '_blank');
                });
        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function ajax_populateShipEntry($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtShipEntry'
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
                    targets: [7,8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        if (rowData.qty === rowData.balance_supplier) {
                            $(td).css('color', 'green');
                        } else {
                            $(td).css('color', 'red');
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width:'8%' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'fromto_trace_no', name: 'fromto_trace_no', className: 'text-center'},
                    { data: 'so_no', name: 'so_no', className: 'text-center'},
                    { data: 'batch_no', name: 'batch_no', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        };
        function ajax_postCancelShip($idShipHead, $idTraceHead, $traceNo, $fromTraceNo){
            $.ajax({
                url: post_url,
                method: "POST",
                dataType: "JSON",
                data:{
                    flag: 'post_cancelShip',
                    idShipHead: $idShipHead,
                    idTraceHead: $idTraceHead,
                    traceNo: $traceNo,
                    fromTraceNo: $fromTraceNo
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
            ajax_populateShipEntry($dt_shipEntry);
        };

    /* FUNCTION AUTO-REFRESH */



</script>
@endpush
