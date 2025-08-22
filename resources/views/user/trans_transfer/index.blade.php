@extends('layouts.app_user')
@section('title', 'Transfer Inter-Plant Entry')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="new-transfer-entry" class="btn btn-primary" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> New Transfer Entry </button>
                                <button type="button" class="btn btn-danger">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-transfer-list" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Entry Date</th>
                                            <th>Matl Doc</th>
                                            <th>Trace No</th>
                                            <th>Material</th>
                                            <th>Sloc (From >>> To)</th>
                                            <th>Init Material (MT)</th>
                                            <th>Init Supplier (MT)</th>
                                            <th>On-Hand Material (MT)</th>
                                            <th>On-Hand Supplier (MT)</th>
                                            <th>Supplier / Batch SAP / Init Qty (MT) / TO Sloc On-hand Qty (MT)</th>
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
    @include('user.trans_transfer.modals.__transferEntry')
    @include('user.trans_transfer.modals.__addMaterialDocModal')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('transfer.index') }}";
        var post_url    = "{{ route('transfer.store') }}";
        var show_url    = "{{ route('transfer.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $modal_addMaterialDoc             = '#modal-materialdoc-add';
        const $modal_transferEntry              = '#modal-transferEntry';

        const $btn_transferEntry_add            = '#new-transfer-entry';
        const $btn_transferEntry_delete         = '#destroy-transfer-entry';
        const $dt_transferEntry                 = '#table-transfer-list';

        const $btn_feed_addDocNo                = '#feed-addDocNo';
        const $btn_feed_editDocNo               = '#feed-editDocNo';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_feed_addDocNo, function(){
                    var $idTraceHead = $(this).attr('data-idTraceHead');

                    $($modal_addMaterialDoc).modal('show');
                    $($txt_materialdoc_flag).val('post_matlDocNumber');
                    $($txt_materialdoc_mode).val('ADD');
                    $($txt_materialdoc_id).val($idTraceHead);
                });
                $(document).on('click', $btn_feed_editDocNo, function(){
                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $docNumber = $(this).attr('data-number');

                    $($modal_addMaterialDoc).modal('show');
                    $($txt_materialdoc_flag).val('post_matlDocNumber');
                    $($txt_materialdoc_mode).val('UPDATE');
                    $($txt_materialdoc_id).val($idTraceHead);
                    $($txt_materialdoc_number).val($docNumber);
                });
                $(document).on('click', $btn_transferEntry_add, function(){
                    $($modal_transferEntry).modal('show');
                    initializeTransferEntry('ADD');
                });
                $(document).on('click', $btn_transferEntry_delete, function(){
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
                            ajax_deactivateTransferEntry($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_deactivateTransferEntry($href){
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
                        $($dt_transferEntry).DataTable().ajax.reload();
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
        function ajax_dtTransferList($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtTransferList'
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
                    }
                },
                {
                    targets: [7,8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        if (rowData.init_qty === rowData.balance_supplier) {
                            $(td).css('color', 'green');
                        } else {
                            $(td).css('color', 'red');
                        }
                    }
                },
                {
                    targets: [9,10], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        if (rowData.qty === rowData.qty_supplier) {
                            $(td).css('color', 'green');
                        } else {
                            $(td).css('color', 'red');
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'trace_no', name: 'trace_no', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'init_qty', name: 'init_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'qty_supplier', name: 'qty_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'}
                ]
            });
        };

    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_page(){
            ajax_dtTransferList($dt_transferEntry);
        };

    /* FUNCTION AUTO-REFRESH */


</script>
@endpush
