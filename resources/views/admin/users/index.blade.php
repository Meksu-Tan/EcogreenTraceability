@extends('layouts.app_admin')
@section('title-head','PTEO EUDR-TS')
@section('title','Admin Dashboard - User Management')
@section('content')

<section class="section">
	<div class="section-body">
        <div class="row">
            <div class="col-md-2" style="padding-bottom:15px">
                <button type="button" id="register-user" class="btn btn-primary"><i class="fas fa-gem" aria-hidden="true"></i> &nbsp Register </button>
            </div>
        </div>
        <div class="row">
			<div class="col-md-12">
				<div class="card">
					<div class="card-body">
                        <div class="table-responsive">
							<table class="table table-striped dataTable no-footer" id="table-user" width="100%" role="grid" aria-describedby="table-1_info">
								<thead>
									<tr>
										<th width="7%">No</th>
                                        <th>Name</th>
										<th>Email</th>
                                        <th>Role</th>
                                        <th>Plant</th>
                                        <th>Action</th>
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


@endsection

@push('js')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('admin.index') }}";
        var post_url    = "{{ route('admin.store') }}";
        var show_url    = "{{ route('admin.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $btn_registerUser        = '#register-user';
        const $btn_updateUser          = '#update-user';
        const $btn_resetPassword       = '#reset-password';
        const $btn_destroyUser         = '#destroy-user';
        const $dt_tableUser            = '#table-user';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                populate_dtTableUser($dt_tableUser);

            /* LISTENER ON MODAL FORM SUBMIT */


            /* LISTENER ON SINGLE-CLICK */
                $(document).on('click', $btn_registerUser, function(){
                    window.location.href = "{{ route('register') }}";
                });
                $(document).on('click', $btn_destroyUser, function(){
                    var id = $(this).attr('data-id');
                    var href = $(this).attr('data-href');

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
                            $.ajax({
                                url: href,
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
                                            timer: 1500
                                        })
                                        $($dt_tableUser).DataTable().ajax.reload();
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Oops...',
                                            text: 'Something went wrong!',
                                        })
                                    }
                                }
                            })
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        }
                    })
                });
                $(document).on('click', $btn_resetPassword, function(){
                    var href = $(this).attr('data-href');
                    window.location.href = href;
                });
                $(document).on('click', $btn_updateUser, function(){

                });
            /* LISTENER ON INPUT CHANGE */

        });

    /* FUNCTION DATATABLE */
        function populate_dtTableUser($id){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_userData'
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
                    { data: 'name', name: 'name', className: 'text-left'  },
                    { data: 'email', name: 'email', className: 'text-left' },
                    { data: 'role', name: 'role', className: 'text-left'},
                    { data: 'plant', name: 'plant', className: 'text-left'},
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });
        };

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION INITIALIZE */



</script>

@endpush
