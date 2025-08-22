<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-viewForwardTrace">
    <div class="modal-dialog" role="document" style="max-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="label-viewForwardTrace">Forward Trace Detail</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="table-responsive">
                                        <table class="table table-striped dataTable no-footer" id="table-viewForwardTrace" width="100%" role="grid" aria-describedby="table-1_info">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Path</th>
                                                    <th>Prev Batch</th>
                                                    <th>Curr Batch</th>
                                                    <th>Batch Date</th>
                                                    <th>Material</th>
                                                    <th>RM/WIP/WHx In_Qty (MT)</th>
                                                    <th>SLoc</th>
                                                    <th>RM/WIP/WHx Out_Qty (MT)</th>
                                                    <th>Supplier / Batch SAP / Init Qty (MT) / Remark</th>
                                                    <th>Matl Doc</th>
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
        </div>
    </div>
</div>
@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('forward.index') }}";
        var post_url    = "{{ route('forward.store') }}";
        var show_url    = "{{ route('forward.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $table_viewForwardTrace       = '#table-viewForwardTrace';
        const $label_viewForwardTrace       = '#label-viewForwardTrace';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */


        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function ajax_dtForwardTrace($id, $idHeader=null, $traceNo=null, $idMaterial=null){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtForwardTrace',
                        idHeader: $idHeader,
                        traceNo: $traceNo,
                        idMaterial: $idMaterial
                    }
                },
                order: [[ 0, 'desc']],
                responsive: true,
                // paging: false,
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
                    { data: 'path', name: 'path', className: 'text-left'},
                    { data: 'from_trace_no', name: 'from_trace_no', className: 'text-center'},
                    { data: 'trace_no', name: 'trace_no', className: 'text-center'},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                ]
            });
        };


    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_forwardTrace($idHeader, $traceNo, $idMaterial){
            ajax_dtForwardTrace($table_viewForwardTrace, $idHeader, $traceNo, $idMaterial);
            $($label_viewForwardTrace).html('Forward Trace Detail (' + $traceNo + ')' );
        };


</script>
@endpush
