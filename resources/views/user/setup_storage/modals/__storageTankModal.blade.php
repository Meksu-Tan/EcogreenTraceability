supplier<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-storageTank">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-storageTank-header">Setup Storage</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-storageTank" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-storageTank-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-storageTank-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-storageTank-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">ID Plant *</label>
                                        <input type="number" name="id_plant" id="form-storageTank-idPlant" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Storage Type 1</label>
                                        <select name="type" id="form-storageTank-type" style="width: 100%;" class="form-control">
                                            <option value="">No Type</option>
                                            <option value="RM">RM</option>
                                            <option value="WIP">WIP</option>
                                            <option value="PRD">PRD</option>
                                        </select>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Storage Type 2</label>
                                        <input type="text" name="code" id="form-storageTank-code" style="width: 100%; text-transform: uppercase;" class="form-control col-sm-12" autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Storage Tank Description *</label>
                                        <input type="text" name="description" id="form-storageTank-description" style="width: 100%; text-transform: uppercase;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Storage Code for Supplier</label>
                                        <input type="text" name="codeSupplier" id="form-storageTank-codeSupplier" style="width: 100%; text-transform: uppercase;" class="form-control col-sm-12" autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-storageTank">Save</button>
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
        const $form_storageTank             = '#form-storageTank';

        const $txt_storageTank_flag         = '#form-storageTank-flag';
        const $txt_storageTank_mode         = '#form-storageTank-mode';
        const $txt_storageTank_id           = '#form-storageTank-id';

        const $txt_storageTank_code         = '#form-storageTank-code';
        const $txt_storageTank_description  = '#form-storageTank-description';
        const $cmb_storageTank_type         = '#form-storageTank-type';
        const $txt_storageTank_idPlant      = '#form-storageTank-idPlant';
        const $txt_storageTank_codeSupplier = '#form-storageTank-codeSupplier';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_storageTank_modal();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_storageTank).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_storageTank_mode).val();

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

                                        $($dt_storageTank).DataTable().ajax.reload();
                                        $($modal_storageTank).modal('hide');

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
        function initialize_storageTank_modal($flag=null, $mode=null, $id=null, $code=null,
                                              $description=null, $idPlant=null, $type=null,
                                              $codeSupplier=null) {

            $($txt_storageTank_flag).val($flag);
            $($txt_storageTank_mode).val($mode);
            $($txt_storageTank_id).val($id);
            $($txt_storageTank_code).val($code);
            $($txt_storageTank_description).val($description);
            $($txt_storageTank_idPlant).val($idPlant);
            $($cmb_storageTank_type).val($type);
            $($txt_storageTank_codeSupplier).val($codeSupplier);

        };

</script>
@endpush
