@extends('layouts.app_user')
@section('title', $role)

@section('content')

<section class="section">
	<div class="section-body">
        <div class="row">
			<div class="col-md-12">
				<div class="card" style="margin-top:-15px; background-color: black;">
					<div class="card-body">
                        <div class="row">
                            <div class="col-md-12 text-center"> <!-- Modified to center the content -->
                                <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                    <span class="badge badge-dark d-block w-100" id="oee-summary-label-header"
                                          style="font-size:35px; white-space: normal;">EUDR-TS BACKWARD TRACING</span>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-backward-list" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Entry Date</th>
                                            <th>Trace No</th>
                                            <th>SO No</th>
                                            <th>Batch No</th>
                                            <th>Sloc</th>
                                            <th>Product Desc</th>
                                            <th>Qty (MT)</th>
                                            <th>Supplier / Batch SAP / Qty (MT)</th>
                                            <th>Source Trace No / PO</th>
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
    @include('user.dashboard_backward.modals.__viewTraceModal')
    @include('user.trans_shipment.modals.__shipBatchPackagingModal')
    @include('user.trans_shipment.modals.__shipDataShipmentModal')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('backward.index') }}";
        var post_url    = "{{ route('backward.store') }}";
        var show_url    = "{{ route('backward.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $table_backwardList        = '#table-backward-list';
        const $btn_viewBackwardTrace     = '#view-backward-trace';

        const $modal_viewBackwardTrace   = '#modal-viewBackwardTrace';
        const $modal_shipEntryBatch      = '#modal-shipEntryBatch';
        const $modal_shipDataShipment    = '#modal-shipData';

        const $btn_shipEntry_batchDetail = '#view-shipment-batch';
        const $btn_shipEntry_shipDetail  = '#view-shipment-detail';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_viewBackwardTrace, function(e){
                    e.preventDefault();
                    var $idHeader = $(this).attr('data-idHeader');
                    var $traceNo = $(this).attr('data-traceNo');
                    var $idMaterial = $(this).attr('data-idMaterial');

                    ajax_populateBackwardTrace($idHeader, $traceNo, $idMaterial);
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

        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function ajax_dtBackwardList($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtBackwardList'
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
                    { data: 'action', name: 'action', orderable: false, searchable: false},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'trace_no', name: 'trace_no', className: 'text-center'},
                    { data: 'so_no', name: 'so_no', className: 'text-center'},
                    { data: 'batch_no', name: 'batch_no', className: 'text-center'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'},
                    { data: 'source', name: 'source', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                ]
            });
        };
        function ajax_populateBackwardTrace($idHeader, $traceNo, $idMaterial){
            $($modal_viewBackwardTrace).modal('show');
            initialize_backwardTrace($idHeader, $traceNo, $idMaterial);
        };


    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_page(){
            ajax_dtBackwardList($table_backwardList);
        };

    /* FUNCTION AUTO-REFRESH */
        function startAutoRefresh() {
            $intervalId = setInterval(function () {

                var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
                var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('-');
                var currentTime = time_format(new Date());

                //ajax_updateRecord(currentDate, currentTime);

            }, 30000); // 120000 milliseconds = 2 minutes
        }
        function stopAutoRefresh() {
            clearInterval($intervalId); // Clear the interval using the stored interval ID
        }
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
