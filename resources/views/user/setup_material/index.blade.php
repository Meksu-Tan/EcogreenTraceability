@extends('layouts.app_user')
@section('title', 'Setup WIP/PRD Material')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @include('user.setup_material.partials.__buttonHeader')
                    </div>
                </div>
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="material-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> New Material </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-material" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Action</th>
                                            <th>Code</th>
                                            <th>Code (Non-EUDR)</th>
                                            <th>Code (Supplier)</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Flowmeter Feed</th>
                                            <th>Flowmeter Rundown</th>
                                            <th>ID Feed</th>
                                            <th>ID Rundown</th>
                                            <th>For Packaging</th>
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
    @include('user.setup_material.modals.__materialModal')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('materialsetup.index') }}";
        var post_url    = "{{ route('materialsetup.store') }}";
        var show_url    = "{{ route('materialsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $dt_material              = '#table-material';

        const $btn_materialAdd          = '#material-add';
        const $btn_materialUpdate       = '#material-update';
        const $btn_materialDelete       = '#material-destroy';
        const $btn_materialActivate     = '#material-activate';

        const $modal_material           = '#modal-material';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                ajax_populateDtMaterial($dt_material);

            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_materialAdd, function(){
                    $($modal_material).modal('show');
                    $($txt_flagMaterial).val('post_storeMaterial');
                    $($txt_modeMaterial).val('ADD');

                    initialize_modalMaterial()
                });
                $(document).on('click', $btn_materialUpdate, function(){
                    var $id = $(this).attr('data-id');
                    var $code = $(this).attr('data-code');
                    var $code_noneudr = $(this).attr('data-codeNonEudr');
                    var $code_supplier = $(this).attr('data-codeSupplier');
                    var $description = $(this).attr('data-description');
                    var $yield = $(this).attr('data-yield');
                    var $type = $(this).attr('data-type');
                    var $qtf_feed = $(this).attr('data-qtffeed');
                    var $qtf_rundown = $(this).attr('data-qtfrundown');
                    var $status_packaging = $(this).attr('data-statusPackaging');

                    $($modal_material).modal('show');

                    $($txt_flagMaterial).val('post_storeMaterial');
                    $($txt_modeMaterial).val('UPDATE');
                    $($txt_idMaterial).val($id);
                    $($txt_codeMaterial).val($code);
                    $($txt_codeMaterialNonEudr).val($code_noneudr);
                    $($txt_codeMaterialSupplier).val($code_supplier);
                    $($txt_descriptionMaterial).val($description);
                    $($txt_yieldMaterial).val($yield);
                    $($cmb_typeMaterial).val($type);
                    $($txt_qtfFeedMaterial).val($qtf_feed);
                    $($txt_qtfRundownMaterial).val($qtf_rundown);
                    $($cmb_statusPackaging).val($status_packaging);

                });
                $(document).on('click', $btn_materialDelete, function(){
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
                            ajax_deleteMaterial($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_materialActivate, function(){
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
                            ajax_activateMaterial($href);
                        } else {
                            console.log(`data was dismissed by ${willActivate.dismiss}`);
                        };
                    })
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateDtMaterial($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtMaterial'
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
                    "targets": [11],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-check" style="color: green" title="Packaging Matl"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-times" style="color: red" title="Not Packaging Matl"></i>';
                        }
                    },
                },{
                    "targets": [12],
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
                    { data: 'code', name: 'code', className: 'text-center'},
                    { data: 'code_noneudr', name: 'code', className: 'text-center'},
                    { data: 'code_matl_supplier', name: 'code_matl_supplier', className: 'text-center'},
                    { data: 'description', name: 'description', className: 'text-left', width:'20%'},
                    { data: 'type', name: 'type', className: 'text-center'},
                    { data: 'qtf_feed', name: 'qtf_feed', className: 'text-center'},
                    { data: 'qtf_rundown', name: 'qtf_rundown', className: 'text-center'},
                    { data: 'id_feed', name: 'id_feed', className: 'text-center'},
                    { data: 'id_rundown', name: 'id_rundown', className: 'text-center'},
                    { data: 'status_packaging', name: 'status_packaging', className: 'text-center'},
                    { data: 'status', name: 'status', className: 'text-center'},
                    { data: 'created_at', name: 'created_at', className: 'text-left'},
                    { data: 'updated_at', name: 'updated_at', className: 'text-left'},
                ]
            });
        };
        function ajax_deleteMaterial($href){
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
                        $($dt_material).DataTable().ajax.reload();
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
        function ajax_activateMaterial($href){
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
                        $($dt_material).DataTable().ajax.reload();
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
