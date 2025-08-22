<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-storageDetail">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-storageDetail-header">Setup Storage - Tank</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-storageDetail" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-storageDetail-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-storageDetail-id" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id_tank" id="form-storageDetail-idTank" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-storageDetail-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Storage</label>
                                        <input type="text" name="storage" id="form-storageDetail-storage" style="width: 100%; text-transform: uppercase;" class="form-control" readonly autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Tank Number</label>
                                        <input type="text" name="tf_number" id="form-storageDetail-tfNumber" style="width: 100%; text-transform: uppercase;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-storageDetail">Save</button>
                                    </div>
                                </form>
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
        var index_url   = "{{ route('storagesetup.index') }}";
        var post_url    = "{{ route('storagesetup.store') }}";
        var show_url    = "{{ route('storagesetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_storageDetail             = '#form-storageDetail';

        const $txt_storageDetail_flag         = '#form-storageDetail-flag';
        const $txt_storageDetail_mode         = '#form-storageDetail-mode';
        const $txt_storageDetail_id           = '#form-storageDetail-id';
        const $txt_storageDetail_idTank       = '#form-storageDetail-idTank';
        const $txt_storageDetail_storage      = '#form-storageDetail-storage';

        const $txt_storageDetail_tfNumber     = '#form-storageDetail-tfNumber';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_storageDetail_modal();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_storageDetail).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_storageDetail_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' TANK?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, ' + $mode + ' it',
                        cancelButtonText: 'No, cancel',
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                url: post_url,
                                method: "POST",
                                data: formData,
                                contentType: false,
                                cache: false,
                                processData: false,
                                dataType: "JSON",
                                success: function(data) {
                                    if (data.status == 1) {
                                        Swal.fire({
                                            position: 'top-end',
                                            icon: 'success',
                                            title: data.message,
                                            showConfirmButton: false,
                                            timer: 500
                                        });

                                        $($dt_storageDetail).DataTable().ajax.reload();
                                        $($modal_storageDetail).modal('hide');

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });
                });
        });

    /* FUNCTION INITIALIZATION */
        function initialize_storageDetail_modal($flag=null, $mode=null, $idTank=null, $storage=null, $id=null,
                                                $tfNumber=null){

            $($txt_storageDetail_flag).val($flag);
            $($txt_storageDetail_mode).val($mode);
            $($txt_storageDetail_id).val($id);
            $($txt_storageDetail_storage).val($storage);
            $($txt_storageDetail_tfNumber).val($tfNumber);
            $($txt_storageDetail_idTank).val($idTank);
        };

</script>
@endpush
