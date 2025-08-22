<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjPeriodView">
    <div class="modal-dialog" role="document" style="max-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>View Uploaded Data & Adjustment</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <input type="hidden" name="id" id="form-adjPeriodView-id" class="form-control text-uppercase">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="name">Period</label>
                                            <input type="date" name="period" id="form-adjPeriodView-period" style="width: 100%;" class="form-control col-sm-12" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Batch SAP</label>
                                            <input type="text" name="batch" id="form-adjPeriodView-batch" style="width: 100%;" class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div style="padding-left:25px; padding-bottom:10px">
                                        <button type="button" id="btn-adjPeriodView-upload" class="btn btn-primary" style="color:white"><i class="fab fa-audible" aria-hidden="true"></i> 0. Re-upload Excel </button>
                                        <button type="button" id="btn-adjPeriodView-populate" class="btn btn-primary" style="color:white"><i class="fab fa-audible" aria-hidden="true"></i> 1. Calc Qty (On-Hand) </button>
                                        <button type="button" id="btn-adjPeriodView-adjust" class="btn btn-primary" style="color:white"><i class="fab fa-fly" aria-hidden="true"></i> 2. Do Adjustment </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="table-responsive">
                                        <table class="table table-striped dataTable no-footer" id="table-adjPeriodView" width="100%" role="grid" aria-describedby="table-1_info">
                                            <thead>
                                                <tr>
                                                    <th width="7%">No</th>
                                                    <!-- <th>Action</th> -->
                                                    <th>Plant</th>
                                                    <th>Tank</th>
                                                    <th>Sloc</th>
                                                    <th>Material</th>
                                                    <th>Qty (PSPA)</th>
                                                    <th>Qty (On-Hand)</th>
                                                    <th>Total</th>
                                                    <th>Adj.Type</th>
                                                    <th>Adj.No</th>
                                                    <th>Adj.Status</th>
                                                    <th>CalOnHand At</th>
                                                    <th>Created At</th>
                                                    <th>Updated At</th>
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
        var index_url   = "{{ route('adjustment.index') }}";
        var post_url    = "{{ route('adjustment.store') }}";
        var show_url    = "{{ route('adjustment.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $txt_adjPeriodView_id                 = '#form-adjPeriodView-id';
        const $txt_adjPeriodView_period             = '#form-adjPeriodView-period';
        const $txt_adjPeriodView_batch              = '#form-adjPeriodView-batch';

        const $btn_adjPeriodView_populate           = '#btn-adjPeriodView-populate';
        const $btn_adjPeriodView_adjust             = '#btn-adjPeriodView-adjust';
        const $btn_adjPeriodView_upload             = '#btn-adjPeriodView-upload';

        const $tbl_adjPeriodView                    = '#table-adjPeriodView';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */


            /* LISTENER ON CLICK FUNCTION */
                $(document).on('click', $btn_adjPeriodView_populate, function(e){
                    e.preventDefault();

                    $idHead = $($txt_adjPeriodView_id).val();
                    $period = $($txt_adjPeriodView_period).val();

                    Swal.fire({
                        title: "Calc for period " + $period + "?",
                        text: "All transaction entry must be finished first!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, do it!"
                    }).then((result) => {
                        if (result) {
                            ajax_populateOnHand($idHead);
                        }
                    });

                });
                $(document).on('click', $btn_adjPeriodView_adjust, function(e){
                    e.preventDefault();

                    $idHead = $($txt_adjPeriodView_id).val();
                    $period = $($txt_adjPeriodView_period).val();

                    Swal.fire({
                        title: "Adjustment period " + $period + "?",
                        text: "One-time adjustment only!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, adjust it!"
                    }).then((result) => {
                        if (result) {
                            ajax_populateAdjustment($idHead);
                        }
                    });
                });
                $(document).on('click', $btn_adjPeriodView_upload, function(e){
                    var $id = $($txt_adjPeriodView_id).val();
                    ajax_checkAdjustmentStatus($id);
                });

            /* LISTENER ON MODAL STACK */
                $($modal_adjustmentPeriodUpload).on('show.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodView).hasClass('show') ) {
                        $($modal_adjustmentPeriodView).css('opacity', 0.3);
                    }
                });
                $($modal_adjustmentPeriodUpload).on('hidden.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodView).hasClass('show') ) {
                        $($modal_adjustmentPeriodView).css('opacity', 1);
                    }
                });

        });

    /* FUNCTION AJAX */
        function ajax_adjPeriodView_dt($id, $idHead){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_adjPeriodView_dt',
                        idHead: $idHead
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
                    "targets": [10],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-check" style="color: green" title="Success"></i>';
                        } else if ( data == "2" ){
                            return '<i class="fa fa-times" style="color: red" title="Failed"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-question" style="color: orange" title="No Adj"></i>';
                        }
                    },
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    // { data: 'action', name: 'action', orderable: false, searchable: false},
                    { data: 'plant', name: 'plant', className: 'text-center'},
                    { data: 'tank', name: 'tank', className: 'text-center'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left', width: '25%'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'qty_data', name: 'qty_data', className: 'text-right'},
                    { data: 'total', name: 'total', className: 'text-right'},
                    { data: 'adj_type', name: 'adj_type', className: 'text-center'},
                    { data: 'adjust_number', name: 'adjust_number', className: 'text-center'},
                    { data: 'adjust_status', name: 'adjust_status', className: 'text-center'},
                    { data: 'populated_at', name: 'populated_at', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-center'},
                    { data: 'updated_at', name: 'updated_at', className: 'text-center'},
                ]
            });
        };
        function ajax_populateOnHand($idHead){
            $.ajax({
                url: post_url,
                method: "POST",
                data: {
                    flag: 'post_adjPeriodView_onHand',
                    idHead: $idHead
                },
                dataType: "JSON",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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

                        $($tbl_adjPeriodView).DataTable().ajax.reload();

                    } else {
                        Swal.fire(data.message, "", "error");
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    if (errors.file) {
                        //$('#message').html('<p>' + errors.file[0] + '</p>');
                    }
                }
            });
        };
        function ajax_populateAdjustment($idHead){
            $.ajax({
                url: post_url,
                method: "POST",
                data: {
                    flag: 'post_adjPeriodView_adjustment',
                    idHead: $idHead
                },
                dataType: "JSON",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
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

                        $($tbl_adjPeriodView).DataTable().ajax.reload();
                        $($table_adjPeriodHeader).DataTable().ajax.reload();

                    } else {
                        Swal.fire(data.message, "", "error");
                    }
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON.errors;
                    if (errors.file) {
                        //$('#message').html('<p>' + errors.file[0] + '</p>');
                    }
                }
            });
        };
        function ajax_checkAdjustmentStatus($idHead){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_adjustStatus',
                    idHead: $idHead
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $adjStatus = response['data'][0].adjust_status;
                        if ($adjStatus == 0) {
                            $($modal_adjustmentPeriodUpload).modal('show');
                            initialize_adjustmentPeriodUpload('post_adjPeriodHeader_uploadExcel', 'ADD', $id, 'VIEW');
                        } else {
                            Swal.fire({
                            position: 'top-end',
                            icon: 'warning',
                            title: 'Cannot re-upload Excel!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        }
                    }
                }
            });
        }

    /* FUNCTION INIT */
        function initialize_adjustmentPeriodView($id=null, $batch=null, $period=null, $adjustStatus=null){
            $($txt_adjPeriodView_id).val($id);
            $($txt_adjPeriodView_batch).val($batch);
            $($txt_adjPeriodView_period).val($period);

            ajax_adjPeriodView_dt($tbl_adjPeriodView, $id);
        }

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
