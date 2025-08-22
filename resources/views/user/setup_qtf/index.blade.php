@extends('layouts.app_user')
@section('title', 'Reset Quantifier')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="quantifier-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> New Reset Quantifier </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-quantifier" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Flowmeter</th>
                                            <th>Reset Value</th>
                                            <th>Remark</th>
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
    @include('user.setup_qtf.modals.__qtfModal')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('qtfsetup.index') }}";
        var post_url    = "{{ route('qtfsetup.store') }}";
        var show_url    = "{{ route('qtfsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $dt_quantifier              = '#table-quantifier';

        const $btn_quantifierAdd          = '#quantifier-add';
        const $btn_quantifierUpdate       = '#quantifier-update';
        const $btn_quantifierDelete       = '#quantifier-destroy';
        const $btn_quantifierActivate     = '#quantifier-activate';

        const $modal_quantifier           = '#modal-quantifier';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                ajax_populateDtQuantifier($dt_quantifier);

            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_quantifierAdd, function(){
                    $($modal_quantifier).modal('show');
                    $($txt_flagQuantifier).val('post_storeQuantifier');
                    $($txt_modeQuantifier).val('ADD');

                    initialize_modalQuantifier()
                });
                $(document).on('click', $btn_quantifierUpdate, function(){
                    var $id = $(this).attr('data-id');
                    var $flowmeter = $(this).attr('data-flowmeter');
                    var $remark = $(this).attr('data-remark');
                    var $resetDate = $(this).attr('data-resetDate');
                    var $value = $(this).attr('data-value');

                    $($modal_quantifier).modal('show');

                    $($txt_flagQuantifier).val('post_storeQuantifier');
                    $($txt_modeQuantifier).val('UPDATE');
                    $($txt_idQuantifier).val($id);
                    $($cmb_flowmeterQuantifier).val($flowmeter);
                    $($txt_remarkQuantifier).val($remark);
                    $($txt_resetdateQuantifier).val($resetDate);
                    $($txt_valueQuantifier).val($value);
                });
                $(document).on('click', $btn_quantifierDelete, function(){
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
                            ajax_deleteQuantifier($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_quantifierActivate, function(){
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
                            ajax_activateQuantifier($href);
                        } else {
                            console.log(`data was dismissed by ${willActivate.dismiss}`);
                        };
                    })
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateDtQuantifier($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtQuantifier'
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
                    "targets": [5],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-check" style="color: green" title="Active"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-times" style="color: red" title="Not Active"></i>';
                        }
                    },
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width:'8%' },
                    { data: 'flowmeter', name: 'code', className: 'text-center'},
                    { data: 'value', name: 'value', className: 'text-right'},
                    { data: 'remark', name: 'remark', className: 'text-left'},
                    { data: 'status', name: 'status', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                ]
            });
        };
        function ajax_deleteQuantifier($href){
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
                        $($dt_quantifier).DataTable().ajax.reload();
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
        function ajax_activateQuantifier($href){
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
                        $($dt_quantifier).DataTable().ajax.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        })
                    }
                }
            })
        }

    /* FUNCTION DYNAMICS */


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
