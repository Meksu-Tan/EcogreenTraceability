@extends('layouts.app_user')
@section('title', 'Setup Plant')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="plant-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> New Plant </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-plant" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Tank</th>
                                            <th>Code</th>
                                            <th>Plant Description</th>
                                            <th>Total Plant</th>
                                            <th>Status</th>
                                            <th>Created at</th>
                                            <th>Updated at</th>
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
    @include('user.setup_plant.modals.__plantModal')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('plantsetup.index') }}";
        var post_url    = "{{ route('plantsetup.store') }}";
        var show_url    = "{{ route('plantsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $dt_plant              = '#table-plant';

        const $btn_plantAdd          = '#plant-add';
        const $btn_plantUpdate       = '#plant-update';
        const $btn_plantDelete       = '#plant-destroy';
        const $btn_plantActivate     = '#plant-activate';
        const $btn_plantView         = '#plant-view';

        const $modal_plant           = '#modal-plant';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                ajax_populateDt_plant($dt_plant);

            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_plantAdd, function(){
                    $($modal_plant).modal('show');

                    initialize_plant_modal('post_plant_store', 'ADD');
                });
                $(document).on('click', $btn_plantUpdate, function(){
                    var $id = $(this).attr('data-id');
                    var $code = $(this).attr('data-code3');
                    var $type = $(this).attr('data-code2');
                    var $description = $(this).attr('data-description');
                    var $idTank = $(this).attr('data-idTank');

                    $($modal_plant).modal('show');
                    initialize_plant_modal('post_plant_store', 'UPDATE', $id, $code, $description, $idTank, $type);
                });
                $(document).on('click', $btn_plantDelete, function(){
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
                            ajax_plant_delete($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_plantActivate, function(){
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
                            ajax_plant_activate($href);
                        } else {
                            console.log(`data was dismissed by ${willActivate.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_plantView, function(){
                    var $id = $(this).attr('data-id');
                    var $plant = $(this).attr('data-plant');
                    var url = "{{ route('plantsetup.edit', 'plant_detail, :id') }}";
                    url = url.replace(':id', $id + ',' + $plant);

                    window.location.href = url;
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateDt_plant($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_plant_dt'
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
                    "targets": [6],
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
                    { data: 'id_tank', name: 'id_tank', className: 'text-center'},
                    { data: 'code', name: 'code', className: 'text-left'},
                    { data: 'description', name: 'description', className: 'text-left', width:'20%'},
                    { data: 'total_plant', name: 'total_plant', className: 'text-right'},
                    { data: 'status', name: 'status', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'updated_at', name: 'updated_at', className: 'text-left'},
                ]
            });
        };
        function ajax_plant_delete($href){
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
                        $($dt_plant).DataTable().ajax.reload();
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
        function ajax_plant_activate($href){
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
                        $($dt_plant).DataTable().ajax.reload();
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
