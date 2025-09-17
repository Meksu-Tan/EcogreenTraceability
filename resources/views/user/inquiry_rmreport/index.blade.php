@extends('layouts.app_user')
@section('title', 'RM to PRODUCT Report')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row" style="padding-left:15px">
                            <h4>Summary of Raw Material to Product</h4>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <select name="entryYear" id="entryYear" class="form-control col-sm-12" style="width: 100%;">

                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableReport" width="100%" role="grid" aria-describedby="table-1_info">
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
                                            <th>Init (MT)</th>
                                            <th>On-Hand (MT)</th>
                                            <th>Supplier / Batch SAP / Init Qty (MT)</th>
                                            <th>On-WIP (MT)</th>
                                            <th>On-PRD (MT)</th>
                                            <th>On-ADJOUT (MT)</th>
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
    @include('user.inquiry_rmreport.modals.__detail')
    @include('user.trans_shipment.modals.__shipDataShipmentModal')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('rmreport.index') }}";
        var post_url    = "{{ route('rmreport.store') }}";
        var show_url    = "{{ route('rmreport.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $entryYear            = '#entryYear';
        const $tableReport          = '#tableReport';
        const $viewDetail           = '#view-detail';

        const $modal_viewDetail         = '#modal-viewDetail';
        const $modal_shipDataShipment   = '#modal-shipData';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */
                $(document).on('change', $entryYear, function(){
                    populate_tableReport($tableReport, $($entryYear).val());
                });


            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $viewDetail, function(){
                    $batchSap = $(this).attr('data-batchSap');
                    $initQty = $(this).attr('data-initQty');

                    $($modal_viewDetail).modal('show');
                    $($modal_header).html("Detail RM Traceability ( Batch SAP : " + $batchSap + " || QTY RM : " + $initQty + " MT )" );
                    populate_tableReportDetail_onTank($modal_tableReportDetail1, 'get_dtDetailRmPrd_onTank', $batchSap);
                    populate_tableReportDetail_onWarehouse($modal_tableReportDetail2, 'get_dtDetailRmPrd_onWarehouse', $batchSap);
                    populate_tableReportDetail_onAdjOut($modal_tableReportDetail3, 'get_dtDetailRmPrd_onAdjOut', $batchSap);
                });
            /* LISTENER ON MODAL STACK */
                $($modal_shipDataShipment).on('show.bs.modal', function () {
                    if ( $($modal_viewDetail).hasClass('show') ) {
                        $($modal_viewDetail).css('opacity', 0.3);
                    }
                });
                $($modal_shipDataShipment).on('hidden.bs.modal', function () {
                    if ( $($modal_viewDetail).hasClass('show') ) {
                        $($modal_viewDetail).css('opacity', 1);
                    }
                });
        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function populate_tableReport($id, $selectedYear){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtSummaryRmPrd',
                        selectedYear: $selectedYear
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
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'},
                    { data: 'qty_tank', name: 'qty_tank', className: 'text-right'},
                    { data: 'qty_warehouse', name: 'qty_warehouse', className: 'text-right'},
                    { data: 'qty_adjustment', name: 'qty_adjustment', className: 'text-right'},
                ]
            });
        };
        function populate_tableReportDetail_onTank($id, $flag, $batchSap){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                paging: false,
                ajax: {
                    url: show_url,
                    data: {
                        flag: $flag,
                        batchSap: $batchSap
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
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center', width:"3%" },
                    { data: 'sloc', name: 'sloc', className: 'text-left', width:"15%"},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right', width:"12%"},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right', width:"12%"},
                    { data: 'balance', name: 'balance', className: 'text-right', width:"12%"}
                ]
            });
        };
        function populate_tableReportDetail_onWarehouse($id, $flag, $batchSap){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                paging: false,
                ajax: {
                    url: show_url,
                    data: {
                        flag: $flag,
                        batchSap: $batchSap
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
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center', width:"3%" },
                    { data: 'sloc', name: 'sloc', className: 'text-left', width:"15%"},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right', width:"12%"},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right', width:"12%"},
                    { data: 'balance', name: 'balance', className: 'text-right', width:"12%"},
                    { data: 'shipment', name: 'shipment', className: 'text-left'},
                ]
            });
        };
        function populate_tableReportDetail_onAdjOut($id, $flag, $batchSap){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                paging: false,
                ajax: {
                    url: show_url,
                    data: {
                        flag: $flag,
                        batchSap: $batchSap
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
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center', width:"3%" },
                    { data: 'sloc', name: 'sloc', className: 'text-left', width:"15%"},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right', width:"12%"},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right', width:"12%"},
                    { data: 'balance', name: 'balance', className: 'text-right', width:"12%"}
                ]
            });
        };


    /* FUNCTION DYNAMICS */



    /* FUNCTION INITIALIZATION */



    /* FUNCTION AUTO-REFRESH */
        function initialize_page(){
            const currentYear = new Date().getFullYear();
            const numberOfYears = 5;

            for (let i = 0; i < numberOfYears; i++) {
                const year = currentYear - i;
                $('#entryYear').append(
                    $('<option>', {
                        value: year,
                        text: year
                    })
                );
            }

            populate_tableReport($tableReport, currentYear);
        };
        function feed_render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var currentTime = time_format(new Date());

            $($entryDate).val(currentDate);
        };
        function time_format(d) {
            hours = format_two_digits(d.getHours());
            minutes = format_two_digits(d.getMinutes());
            return hours + ":" + minutes;
        };
        function format_two_digits(n) {
            return n < 10 ? '0' + n : n;
        };

</script>
@endpush
