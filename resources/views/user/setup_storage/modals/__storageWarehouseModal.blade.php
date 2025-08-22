supplier<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-warehouse">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-warehouse-header">Setup Warehouse</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-warehouse" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-warehouse-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-warehouse-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-warehouse-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">ID Batch (P1/P2/13/14/...) *</label>
                                        <input type="text" name="id_batch" id="form-warehouse-idBatch" style="width: 100%; text-transform: uppercase;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Warehouse Code *</label>
                                        <input type="number" name="code" id="form-warehouse-code" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Description *</label>
                                        <input type="text" name="description" id="form-warehouse-description" style="width: 100%; text-transform: uppercase;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-warehouse">Save</button>
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
        const $form_warehouse             = '#form-warehouse';

        const $txt_warehouse_flag         = '#form-warehouse-flag';
        const $txt_warehouse_mode         = '#form-warehouse-mode';
        const $txt_warehouse_id           = '#form-warehouse-id';

        const $txt_warehouse_code         = '#form-warehouse-code';
        const $txt_warehouse_description  = '#form-warehouse-description';
        const $txt_warehouse_idBatch      = '#form-warehouse-idBatch';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_warehouse_modal();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_warehouse).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_warehouse_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' STORAGE?',
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

                                        $($dt_warehouse).DataTable().ajax.reload();
                                        $($modal_warehouse).modal('hide');

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
        function initialize_warehouse_modal($flag=null, $mode=null, $id=null, $code=null,
                                              $description=null, $idBatch=null){
            $($txt_warehouse_flag).val($flag);
            $($txt_warehouse_mode).val($mode);
            $($txt_warehouse_id).val($id);
            $($txt_warehouse_code).val($code);
            $($txt_warehouse_description).val($description);
            $($txt_warehouse_idBatch).val($idBatch);

        };

</script>
@endpush
