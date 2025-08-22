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
                                          style="font-size:35px; white-space: normal;">EUDR-TS FORWARD TRACING</span>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-forward-list" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>RM Batch No</th>
                                            <th>Entry Date</th>
                                            <th>Matl Doc</th>
                                            <th>Material</th>
                                            <th>Tank</th>
                                            <th>Init (MT)</th>
                                            <th>On-Hand (MT)</th>
                                            <th>Supplier / Batch SAP / Init Qty (MT) / Remark</th>
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
    @include('user.dashboard_forward.modals.__viewTraceModal')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('forward.index') }}";
        var post_url    = "{{ route('forward.store') }}";
        var show_url    = "{{ route('forward.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $table_forwardList        = '#table-forward-list';
        const $btn_viewForwardTrace     = '#view-forward-trace';

        const $modal_viewForwardTrace   = '#modal-viewForwardTrace';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_viewForwardTrace, function(e){
                    e.preventDefault();
                    var $idHeader = $(this).attr('data-idHeader');
                    var $traceNo = $(this).attr('data-traceNo');
                    var $idMaterial = $(this).attr('data-idMaterial');

                    ajax_populateForwardTrace($idHeader, $traceNo, $idMaterial);
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function ajax_dtForwardList($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtForwardList'
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
                    { data: 'trace_no', name: 'trace_no', className: 'text-center'},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-center'},
                    { data: 'tf_number', name: 'tf_number', className: 'text-center'},
                    { data: 'init_qty', name: 'init_qty', className: 'text-right'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                ]
            });
        };
        function ajax_populateForwardTrace($idHeader, $traceNo, $idMaterial){
            $($modal_viewForwardTrace).modal('show');
            initialize_forwardTrace($idHeader, $traceNo, $idMaterial);
        };


    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_page(){
            ajax_dtForwardList($table_forwardList);
        };

    /* FUNCTION AUTO-REFRESH */
        function startAutoRefresh() {
            $intervalId = setInterval(function () {

                var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
                var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('-');
                var currentTime = time_format(new Date());

                $($status_oeeMonitoring_time).html('last update ' + currentTime);
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
