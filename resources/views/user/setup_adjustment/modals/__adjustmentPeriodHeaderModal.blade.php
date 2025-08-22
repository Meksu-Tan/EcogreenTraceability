<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjPeriodHeader">
    <div class="modal-dialog" role="document" style="max-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Stock Period Adjustment Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div style="padding-left:25px; padding-bottom:10px">
                                        <button type="button" id="btn-adjPeriodHeader-new" class="btn btn-primary" style="color:white"><i class="fab fa-accusoft" aria-hidden="true"></i> Create New Period </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="table-responsive">
                                        <table class="table table-striped dataTable no-footer" id="table-adjPeriodHeader" width="100%" role="grid" aria-describedby="table-1_info">
                                            <thead>
                                                <tr>
                                                    <th width="7%">No</th>
                                                    <th>Action</th>
                                                    <th>Period</th>
                                                    <th>Batch</th>
                                                    <th>Uploaded File</th>
                                                    <th>Adjust Status</th>
                                                    <th>Lock Status</th>
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
        const $btn_adjPeriodHeader_new           = '#btn-adjPeriodHeader-new';
        const $btn_adjPeriodHeader_delete        = '#adjustmentPeriodHeader-destroy';
        const $btn_adjPeriodHeader_update        = '#adjustmentPeriodHeader-update';
        const $btn_adjPeriodHeader_upload        = '#adjustmentPeriodHeader-upload';
        const $btn_adjPeriodHeader_view          = '#adjustmentPeriodHeader-view';
        const $btn_adjPeriodHeader_unlock        = '#adjustmentPeriodHeader-unlock';
        const $btn_adjPeriodHeader_lock          = '#adjustmentPeriodHeader-lock';

        const $table_adjPeriodHeader             = '#table-adjPeriodHeader';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */


            /* LISTENER ON CLICK FUNCTION */
                $(document).on('click', $btn_adjPeriodHeader_new, function(){
                    $($modal_adjustmentPeriodNew).modal('show');

                    var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
                    var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
                    initialize_adjustmentPeriodNew('post_adjPeriodHeader', 'ADD', null, currentDate);
                });
                $(document).on('click', $btn_adjPeriodHeader_delete, function(){
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
                            ajax_adjustmentPeriodHeader_delete($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_adjPeriodHeader_update, function(e){
                    /* ON UPDATE FROM BALANCE_TABLE */
                    e.preventDefault();
                    var $id = $(this).attr('data-id');
                    var $period = $(this).attr('data-period');
                    var $batch = $(this).attr('data-batch');

                    $($modal_adjustmentPeriodNew).modal('show');
                    initialize_adjustmentPeriodNew('post_adjPeriodHeader', 'UPDATE', $id, $period, $batch);
                });
                $(document).on('click', $btn_adjPeriodHeader_upload, function(e){
                    e.preventDefault();
                    var $id = $(this).attr('data-id');

                    $($modal_adjustmentPeriodUpload).modal('show');
                    initialize_adjustmentPeriodUpload('post_adjPeriodHeader_uploadExcel', 'ADD', $id, 'HEADER');
                });
                $(document).on('click', $btn_adjPeriodHeader_view, function(e){
                    e.preventDefault();

                    var $id = $(this).attr('data-id');
                    var $batch = $(this).attr('data-batch');
                    var $period = $(this).attr('data-period');
                    var $adjustStatus = $(this).attr('data-adjustStatus');

                    $($modal_adjustmentPeriodView).modal('show');
                    initialize_adjustmentPeriodView($id, $batch, $period, $adjustStatus);
                });
                $(document).on('click', $btn_adjPeriodHeader_lock, function(e){
                    e.preventDefault();

                    var $id = $(this).attr('data-id');
                    Swal.fire({
                        title: "Lock Period?",
                        text: "All transaction entry must be finished first!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, do it!"
                    }).then((result) => {
                        if (result) {
                            ajax_lockPeriod($id, 1);
                        }
                    });

                });
                $(document).on('click', $btn_adjPeriodHeader_unlock, function(e){
                    e.preventDefault();

                    var $id = $(this).attr('data-id');
                    Swal.fire({
                        title: "Un-Lock Period?",
                        text: "OnHand vs PSPA Qty may not tally!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, do it!"
                    }).then((result) => {
                        if (result) {
                            ajax_lockPeriod($id, 0);
                        }
                    });
                });

            /* LISTENER ON MODAL STACK */
                $($modal_adjustmentPeriodNew).on('show.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 0.3);
                    }
                });
                $($modal_adjustmentPeriodNew).on('hidden.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 1);
                    }
                });
                $($modal_adjustmentPeriodUpload).on('show.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 0.3);
                    }
                });
                $($modal_adjustmentPeriodUpload).on('hidden.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 1);
                    }
                });
                $($modal_adjustmentPeriodView).on('show.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 0.3);
                    }
                });
                $($modal_adjustmentPeriodView).on('hidden.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 1);
                    }
                });

        });

    /* FUNCTION AJAX */
        function ajax_adjustmentPeriodHeader_dt($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_adjustmentPeriodHeader_dt'
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
                    "targets": [4,5],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-check" style="color: green" title="Active"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-times" style="color: red" title="Not Active"></i>';
                        }
                    },
                },{
                    "targets": [6],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-lock" style="color: green" title="Active"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-unlock" style="color: red" title="Not Active"></i>';
                        }
                    },
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '15%' },
                    { data: 'period', name: 'period', className: 'text-center'},
                    { data: 'batch_sap', name: 'batch_sap', className: 'text-left', width: '35%'},
                    { data: 'uploaded_file', name: 'uploaded_file', className: 'text-center'},
                    { data: 'adjust_status', name: 'adjust_status', className: 'text-center'},
                    { data: 'lock_status', name: 'lock_status', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-center'},
                    { data: 'updated_at', name: 'updated_at', className: 'text-center'},
                ]
            });
        };
        function ajax_adjustmentPeriodHeader_delete($href){
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
                        $($table_adjPeriodHeader).DataTable().ajax.reload();
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
        function ajax_lockPeriod($id, $lockStatus){
            $.ajax({
                url: post_url,
                method: "POST",
                data: {
                    flag: 'post_adjPeriodHeader_lock',
                    idHead: $id,
                    lockStatus: $lockStatus
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

    /* FUNCTION INIT */
        function initialize_adjustmentPeriodHeader(){
            ajax_adjustmentPeriodHeader_dt($table_adjPeriodHeader);
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
